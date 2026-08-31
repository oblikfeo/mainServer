<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Services\Payments\BonusExtraDevicePricing;
use App\Services\Platega\PlategaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class CabinetCreatePaymentLinkController extends Controller
{
    public function __invoke(Request $request, PlategaClient $platega, BonusExtraDevicePricing $bonusPricing): JsonResponse
    {
        if (! $platega->isConfigured()) {
            return response()->json(['error' => 'payments_not_configured'], 503);
        }

        $user = $request->user();

        $data = $request->validate([
            'plan' => ['required_unless:purpose,extra_device', 'nullable', 'string', 'max:32'],
            'period' => ['required_unless:purpose,extra_device', 'nullable', 'string', 'max:32'],
            'purpose' => ['nullable', 'string', 'in:new,renew,extra_device'],
            'subscription_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $plan = (string) ($data['plan'] ?? '');
        $period = (string) ($data['period'] ?? '');
        $purpose = (string) ($data['purpose'] ?? 'new');

        if ($purpose === 'extra_device') {
            return $this->createExtraDeviceOrder($request, $platega, $user, $data, $bonusPricing);
        }

        if ($purpose === 'renew') {
            return $this->createRenewalOrder($request, $platega, $user, $plan, $period, $data);
        }

        $products = config('payments.products', []);
        $planCfg = is_array($products) ? ($products[$plan] ?? null) : null;
        $rows = is_array($planCfg) ? ($planCfg['rows'] ?? null) : null;
        $row = is_array($rows) ? ($rows[$period] ?? null) : null;
        if (! is_array($row)) {
            return response()->json(['error' => 'unknown_tariff'], 422);
        }

        $devices = (int) ($planCfg['devices'] ?? 0);
        $days = (int) ($row['days'] ?? 0);
        $quotaGb = (int) ($row['quota_gb'] ?? 0);
        $amountRub = (int) ($row['amount_rub'] ?? 0);
        if ($devices < 1 || $days < 1 || $quotaGb < 1 || $amountRub < 1) {
            throw new RuntimeException('Неверная конфигурация payments.products для '.$plan.' / '.$period);
        }

        $orderId = 'ord_'.(string) Str::ulid();
        $claimToken = Str::random(48);
        $desc = 'Подписка '.$plan.' · '.$period;

        $order = PaymentOrder::query()->create([
            'order_id' => $orderId,
            'claim_token' => $claimToken,
            'user_id' => $user->id,
            'subscription_id' => null,
            'purpose' => 'new',
            'provider' => 'platega',
            'status' => 'created',
            'amount_rub' => $amountRub,
            'currency' => 'RUB',
            'description' => $desc,
            'tariff_plan' => $plan,
            'tariff_period' => $period,
            'days' => $days,
            'devices' => $devices,
            'quota_gb' => $quotaGb,
        ]);

        return $this->startPayment($platega, $order, $request, $claimToken, $user);
    }

    /**
     * @param  array{subscription_id?: int|null}  $data
     */
    private function createRenewalOrder(
        Request $request,
        PlategaClient $platega,
        $user,
        string $plan,
        string $period,
        array $data,
    ): JsonResponse {
        $subscriptionId = isset($data['subscription_id']) ? (int) $data['subscription_id'] : 0;
        if ($subscriptionId < 1) {
            return response()->json(['error' => 'subscription_required'], 422);
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::query()
            ->whereKey($subscriptionId)
            ->where('user_id', $user->id)
            ->first();
        if ($subscription === null) {
            return response()->json(['error' => 'subscription_not_found'], 422);
        }

        $soloCap = (int) config('payments.products.solo.devices', 2);
        $expectedPlan = $subscription->devices <= $soloCap ? 'solo' : 'family';
        if ($plan !== $expectedPlan) {
            return response()->json(['error' => 'plan_mismatch'], 422);
        }

        $renewals = config('payments.renewals', []);
        $planCfg = is_array($renewals) ? ($renewals[$plan] ?? null) : null;
        $rows = is_array($planCfg) ? ($planCfg['rows'] ?? null) : null;
        $row = is_array($rows) ? ($rows[$period] ?? null) : null;
        if (! is_array($row)) {
            return response()->json(['error' => 'unknown_renewal'], 422);
        }

        $addDays = (int) ($row['days'] ?? 0);
        $addQuotaGb = (int) ($row['quota_gb'] ?? 0);
        $amountRub = (int) ($row['amount_rub'] ?? 0);
        $addDevices = (int) ($planCfg['add_devices'] ?? 0);
        if ($addDays < 1 || $addQuotaGb < 1 || $amountRub < 1) {
            throw new RuntimeException('Неверная конфигурация payments.renewals для '.$plan.' / '.$period);
        }
        if ($addDevices < 0 || $addDevices > 100) {
            return response()->json(['error' => 'invalid_configuration'], 500);
        }

        $orderId = 'ord_'.(string) Str::ulid();
        $claimToken = Str::random(48);
        $desc = 'Продление #'.$subscription->public_code.' · '.$plan.' · '.$period;

        $order = PaymentOrder::query()->create([
            'order_id' => $orderId,
            'claim_token' => $claimToken,
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'purpose' => 'renew',
            'provider' => 'platega',
            'status' => 'created',
            'amount_rub' => $amountRub,
            'currency' => 'RUB',
            'description' => $desc,
            'tariff_plan' => $plan,
            'tariff_period' => $period,
            'days' => $addDays,
            'devices' => $addDevices,
            'quota_gb' => $addQuotaGb,
        ]);

        return $this->startPayment($platega, $order, $request, $claimToken, $user);
    }

    /**
     * @param  array{subscription_id?: int|null}  $data
     */
    private function createExtraDeviceOrder(
        Request $request,
        PlategaClient $platega,
        $user,
        array $data,
        BonusExtraDevicePricing $bonusPricing,
    ): JsonResponse {
        $subscriptionId = isset($data['subscription_id']) ? (int) $data['subscription_id'] : 0;
        if ($subscriptionId < 1) {
            return response()->json(['error' => 'subscription_required'], 422);
        }

        if (! $bonusPricing->isConfigured()) {
            return response()->json(['error' => 'invalid_configuration'], 500);
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::query()
            ->whereKey($subscriptionId)
            ->where('user_id', $user->id)
            ->where('is_trial', false)
            ->first();
        if ($subscription === null) {
            return response()->json(['error' => 'subscription_not_found'], 422);
        }
        if ($subscription->isExpired()) {
            return response()->json(['error' => 'subscription_expired'], 422);
        }

        $addDevices = $bonusPricing->addDevices();
        if ($addDevices < 1 || $addDevices > 100) {
            return response()->json(['error' => 'invalid_configuration'], 500);
        }

        $remainingDays = $bonusPricing->remainingActiveDays($subscription);
        $amountRub = $bonusPricing->amountRubForSubscription($subscription);
        if ($amountRub < 1) {
            return response()->json(['error' => 'subscription_expired'], 422);
        }

        $orderId = 'ord_'.(string) Str::ulid();
        $claimToken = Str::random(48);
        $tierRange = $bonusPricing->tierRangeLabel($remainingDays);
        $desc = 'Бонус +'.$addDevices.' устр. · №'.$subscription->public_code.' · '.$tierRange.' · '.$amountRub.' ₽';

        $order = PaymentOrder::query()->create([
            'order_id' => $orderId,
            'claim_token' => $claimToken,
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'purpose' => 'extra_device',
            'provider' => 'platega',
            'status' => 'created',
            'amount_rub' => $amountRub,
            'currency' => 'RUB',
            'description' => $desc,
            'tariff_plan' => 'bonus',
            'tariff_period' => 'extra_device',
            'days' => $remainingDays ?? 0,
            'devices' => $addDevices,
            'quota_gb' => 0,
        ]);

        return $this->startPayment($platega, $order, $request, $claimToken, $user);
    }

    /**
     * Создаёт транзакцию Platega под уже сохранённый заказ и отдаёт ссылку на оплату.
     * Способ оплаты не навязываем — Platega сама показывает выбор (СБП / карта).
     */
    private function startPayment(
        PlategaClient $platega,
        PaymentOrder $order,
        Request $request,
        string $claimToken,
        $user,
    ): JsonResponse {
        $tx = $platega->createTransaction(
            amountRub: (int) $order->amount_rub,
            description: (string) $order->description,
            returnUrl: $this->paymentDoneUrl($request, $claimToken),
            failedUrl: (string) config('platega.site_failed_url'),
            payload: (string) $order->order_id,
            paymentMethod: null,
            metadata: [
                'userId' => (string) $user->id,
                'userName' => 'web:'.$user->id,
            ],
        );

        $order->provider_transaction_id = (string) $tx['transactionId'];
        $order->status = 'pending';
        $order->provider_payload = $tx['raw'] ?? $tx;
        $order->save();

        return response()->json([
            'url' => $tx['url'],
        ]);
    }

    private function paymentDoneUrl(Request $request, string $claimToken): string
    {
        $request->session()->put('cabinet_payment_claim', $claimToken);

        return (string) config('platega.site_return_url');
    }
}

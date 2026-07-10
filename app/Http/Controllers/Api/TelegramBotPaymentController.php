<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Platega\PlategaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

final class TelegramBotPaymentController extends Controller
{
    private const NOT_LINKED = [
        'ok' => false,
        'error' => 'not_linked',
        'message' => 'Telegram не привязан к аккаунту. Откройте Личный кабинет на сайте и привяжите Telegram.',
    ];

    public function catalog(): JsonResponse
    {
        $products = config('payments.products', []);
        $renewals = config('payments.renewals', []);
        $methods = config('platega.payment_methods', []);

        $plans = [];
        foreach (['solo', 'family'] as $planKey) {
            $planCfg = is_array($products[$planKey] ?? null) ? $products[$planKey] : null;
            if (! is_array($planCfg)) {
                continue;
            }
            $rows = is_array($planCfg['rows'] ?? null) ? $planCfg['rows'] : [];
            $periods = [];
            $idx = 0;
            foreach ($rows as $period => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $periods[] = [
                    'index' => $idx,
                    'period' => (string) $period,
                    'amount_rub' => (int) ($row['amount_rub'] ?? 0),
                    'days' => (int) ($row['days'] ?? 0),
                ];
                $idx++;
            }

            $renewRows = is_array($renewals[$planKey]['rows'] ?? null) ? $renewals[$planKey]['rows'] : [];
            $renewPeriods = [];
            $rIdx = 0;
            foreach ($renewRows as $period => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $renewPeriods[] = [
                    'index' => $rIdx,
                    'period' => (string) $period,
                    'amount_rub' => (int) ($row['amount_rub'] ?? 0),
                    'days' => (int) ($row['days'] ?? 0),
                ];
                $rIdx++;
            }

            $plans[] = [
                'plan' => $planKey,
                'label' => $planKey === 'family' ? 'Семейная (5 устр.)' : 'Соло (2 устр.)',
                'devices' => (int) ($planCfg['devices'] ?? 0),
                'periods' => $periods,
                'renew_periods' => $renewPeriods,
            ];
        }

        return response()->json([
            'ok' => true,
            'plans' => $plans,
            'payment_methods' => [
                ['code' => 'sbp', 'label' => '📱 СБП', 'id' => (int) ($methods['sbp'] ?? 2)],
                ['code' => 'card', 'label' => '💳 Картой', 'id' => (int) ($methods['card'] ?? 11)],
            ],
        ]);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
        ]);

        $user = $this->resolveUser((int) $data['telegram_user_id']);
        if ($user === null) {
            return response()->json(self::NOT_LINKED, 404);
        }

        $soloCap = (int) config('payments.products.solo.devices', 2);
        $items = [];

        foreach ($user->subscriptions()->orderByDesc('created_at')->get() as $sub) {
            $plan = $sub->devices <= $soloCap ? 'solo' : 'family';
            $items[] = [
                'id' => (int) $sub->id,
                'plan' => $plan,
                'public_code' => '#'.$sub->public_code,
                'active' => ! $sub->isExpired(),
                'created' => $sub->created_at?->timezone(config('app.timezone'))->format('d.m.Y'),
                'devices' => (int) $sub->devices,
            ];
        }

        return response()->json([
            'ok' => true,
            'items' => $items,
        ]);
    }

    public function create(Request $request, PlategaClient $platega): JsonResponse
    {
        if (! $platega->isConfigured()) {
            return response()->json([
                'ok' => false,
                'error' => 'payments_not_configured',
                'message' => 'Оплата в боте временно недоступна.',
            ], 503);
        }

        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
            'telegram_username' => ['nullable', 'string', 'max:64'],
            'purpose' => ['required', 'string', 'in:new,renew'],
            'plan' => ['required', 'string', 'in:solo,family'],
            'period_index' => ['required', 'integer', 'min:0', 'max:20'],
            'subscription_id' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'in:sbp,card'],
        ]);

        $user = $this->resolveUser((int) $data['telegram_user_id']);
        if ($user === null) {
            return response()->json(self::NOT_LINKED, 404);
        }

        $purpose = (string) $data['purpose'];
        $plan = (string) $data['plan'];
        $periodIndex = (int) $data['period_index'];
        $paymentMethodCode = (string) $data['payment_method'];

        try {
            if ($purpose === 'renew') {
                [$row, $periodLabel, $subscription] = $this->resolveRenewalRow($user, $plan, $periodIndex, $data);
                $order = $this->createOrder(
                    user: $user,
                    purpose: 'renew',
                    plan: $plan,
                    period: $periodLabel,
                    row: $row,
                    devices: (int) config('payments.renewals.'.$plan.'.add_devices', 0),
                    subscriptionId: (int) $subscription->id,
                    description: 'Продление #'.$subscription->public_code.' · '.$plan.' · '.$periodLabel,
                );
            } else {
                [$row, $periodLabel, $devices] = $this->resolveProductRow($plan, $periodIndex);
                $order = $this->createOrder(
                    user: $user,
                    purpose: 'new',
                    plan: $plan,
                    period: $periodLabel,
                    row: $row,
                    devices: $devices,
                    subscriptionId: null,
                    description: 'Подписка '.$plan.' · '.$periodLabel,
                );
            }

            $methodId = $this->paymentMethodId($paymentMethodCode);
            $telegramUserId = (int) $data['telegram_user_id'];
            $username = trim((string) ($data['telegram_username'] ?? ''));
            $userName = $username !== '' ? '@'.ltrim($username, '@') : 'tg:'.$telegramUserId;

            $tx = $platega->createTransaction(
                amountRub: (int) $order->amount_rub,
                description: (string) $order->description,
                returnUrl: (string) config('platega.return_url'),
                failedUrl: (string) config('platega.failed_url'),
                payload: (string) $order->order_id,
                paymentMethod: $methodId,
                metadata: [
                    'userId' => (string) $telegramUserId,
                    'userName' => $userName,
                ],
            );

            $order->provider_transaction_id = $tx['transactionId'];
            $order->status = 'pending';
            $order->provider_payload = $tx['raw'] ?? $tx;
            $order->save();

            return response()->json([
                'ok' => true,
                'order_id' => $order->order_id,
                'pay_url' => $tx['url'],
                'amount_rub' => (int) $order->amount_rub,
                'description' => (string) $order->description,
                'payment_method' => $paymentMethodCode,
                'expires_in' => $tx['expiresIn'],
            ]);
        } catch (RuntimeException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => 'payment_create_failed',
                'message' => 'Не удалось создать платёж. Попробуйте другой способ или позже.',
            ], 502);
        }
    }

    public function status(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
            'order_id' => ['required', 'string', 'max:80'],
        ]);

        $user = $this->resolveUser((int) $data['telegram_user_id']);
        if ($user === null) {
            return response()->json(self::NOT_LINKED, 404);
        }

        $order = PaymentOrder::query()
            ->where('order_id', (string) $data['order_id'])
            ->where('user_id', $user->id)
            ->where('provider', 'platega')
            ->first();

        if ($order === null) {
            return response()->json([
                'ok' => false,
                'error' => 'not_found',
                'message' => 'Заказ не найден.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'status' => (string) $order->status,
            'paid' => $order->status === 'paid',
        ]);
    }

    private function resolveUser(int $telegramUserId): ?User
    {
        return User::query()->where('telegram_id', $telegramUserId)->first();
    }

    /**
     * @return array{0: array<string, mixed>, 1: string, 2: int}
     */
    private function resolveProductRow(string $plan, int $periodIndex): array
    {
        $products = config('payments.products', []);
        $planCfg = is_array($products[$plan] ?? null) ? $products[$plan] : null;
        if (! is_array($planCfg)) {
            throw new RuntimeException('unknown_tariff');
        }

        $rows = is_array($planCfg['rows'] ?? null) ? $planCfg['rows'] : [];
        $period = array_keys($rows)[$periodIndex] ?? null;
        $row = is_array($period) ? null : ($rows[$period] ?? null);
        if (! is_string($period) || ! is_array($row)) {
            throw new RuntimeException('unknown_period');
        }

        $devices = (int) ($planCfg['devices'] ?? 0);
        $days = (int) ($row['days'] ?? 0);
        $quotaGb = (int) ($row['quota_gb'] ?? 0);
        $amountRub = (int) ($row['amount_rub'] ?? 0);
        if ($devices < 1 || $days < 1 || $quotaGb < 1 || $amountRub < 1) {
            throw new RuntimeException('invalid_tariff_config');
        }

        return [$row, $period, $devices];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: string, 2: Subscription}
     */
    private function resolveRenewalRow(User $user, string $plan, int $periodIndex, array $data): array
    {
        $subscriptionId = isset($data['subscription_id']) ? (int) $data['subscription_id'] : 0;
        if ($subscriptionId < 1) {
            throw new RuntimeException('subscription_required');
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::query()
            ->whereKey($subscriptionId)
            ->where('user_id', $user->id)
            ->first();
        if ($subscription === null) {
            throw new RuntimeException('subscription_not_found');
        }

        $soloCap = (int) config('payments.products.solo.devices', 2);
        $expectedPlan = $subscription->devices <= $soloCap ? 'solo' : 'family';
        if ($plan !== $expectedPlan) {
            throw new RuntimeException('plan_mismatch');
        }

        $renewals = config('payments.renewals', []);
        $planCfg = is_array($renewals[$plan] ?? null) ? $renewals[$plan] : null;
        if (! is_array($planCfg)) {
            throw new RuntimeException('unknown_renewal');
        }

        $rows = is_array($planCfg['rows'] ?? null) ? $planCfg['rows'] : [];
        $period = array_keys($rows)[$periodIndex] ?? null;
        $row = is_array($period) ? null : ($rows[$period] ?? null);
        if (! is_string($period) || ! is_array($row)) {
            throw new RuntimeException('unknown_period');
        }

        $addDays = (int) ($row['days'] ?? 0);
        $addQuotaGb = (int) ($row['quota_gb'] ?? 0);
        $amountRub = (int) ($row['amount_rub'] ?? 0);
        if ($addDays < 1 || $addQuotaGb < 1 || $amountRub < 1) {
            throw new RuntimeException('invalid_renewal_config');
        }

        return [$row, $period, $subscription];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function createOrder(
        User $user,
        string $purpose,
        string $plan,
        string $period,
        array $row,
        int $devices,
        ?int $subscriptionId,
        string $description,
    ): PaymentOrder {
        $days = (int) ($row['days'] ?? 0);
        $quotaGb = (int) ($row['quota_gb'] ?? 0);
        $amountRub = (int) ($row['amount_rub'] ?? 0);

        return PaymentOrder::query()->create([
            'order_id' => 'ord_'.(string) Str::ulid(),
            'claim_token' => null,
            'user_id' => $user->id,
            'subscription_id' => $subscriptionId,
            'purpose' => $purpose,
            'provider' => 'platega',
            'status' => 'created',
            'amount_rub' => $amountRub,
            'currency' => 'RUB',
            'description' => $description,
            'tariff_plan' => $plan,
            'tariff_period' => $period,
            'days' => $days,
            'devices' => $devices,
            'quota_gb' => $quotaGb,
        ]);
    }

    private function paymentMethodId(string $code): int
    {
        $methods = config('platega.payment_methods', []);
        $id = (int) ($methods[$code] ?? 0);
        if ($id < 1) {
            throw new RuntimeException('unknown_payment_method');
        }

        return $id;
    }
}

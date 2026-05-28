<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Services\QuickBuy\QuickCheckoutUserCreator;
use App\Services\Wata\WataH2hClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class QuickCheckoutController extends Controller
{
    public function show(): View
    {
        $testPayment = config('payments.quick_buy.test_payment', []);

        return view('quick-buy.index', [
            'testPaymentEnabled' => (bool) ($testPayment['enabled'] ?? false),
            'testPaymentLabel' => (string) ($testPayment['label'] ?? 'Тест: 1 месяц · 10 ₽'),
            'testPaymentAmount' => (int) ($testPayment['amount_rub'] ?? 10),
            'testPaymentPlan' => (string) ($testPayment['plan'] ?? 'solo'),
            'testPaymentPeriod' => (string) ($testPayment['period'] ?? '1 месяц'),
        ]);
    }

    public function pay(Request $request, WataH2hClient $wata, QuickCheckoutUserCreator $userCreator): JsonResponse
    {
        if (trim((string) config('wata.access_token')) === '') {
            return response()->json(['error' => 'payments_not_configured'], 503);
        }

        $data = $request->validate([
            'plan' => ['required', 'string', 'max:32'],
            'period' => ['required', 'string', 'max:32'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'test_checkout' => ['sometimes', 'boolean'],
        ]);

        $plan = (string) $data['plan'];
        $period = (string) $data['period'];
        $testCheckout = (bool) ($data['test_checkout'] ?? false);
        $testCfg = config('payments.quick_buy.test_payment', []);

        if ($testCheckout) {
            if (! (bool) ($testCfg['enabled'] ?? false)) {
                return response()->json(['error' => 'test_payment_disabled'], 403);
            }
            $plan = (string) ($testCfg['plan'] ?? 'solo');
            $period = (string) ($testCfg['period'] ?? '1 месяц');
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
        if ($testCheckout) {
            $amountRub = max(1, (int) ($testCfg['amount_rub'] ?? 10));
        }
        if ($devices < 1 || $days < 1 || $quotaGb < 1 || $amountRub < 1) {
            throw new RuntimeException('Неверная конфигурация payments.products для '.$plan.' / '.$period);
        }

        try {
            return DB::transaction(function () use ($request, $wata, $userCreator, $plan, $period, $devices, $days, $quotaGb, $amountRub, $data, $testCheckout): JsonResponse {
                [$user, $plainPassword] = $userCreator->create((string) $data['email']);

                $orderId = 'ord_'.(string) Str::ulid();
                $claimToken = Str::random(48);
                $desc = 'Подписка '.$plan.' · '.$period.($testCheckout ? ' · тест' : '');

                $order = PaymentOrder::query()->create([
                    'order_id' => $orderId,
                    'claim_token' => $claimToken,
                    'user_id' => $user->id,
                    'subscription_id' => null,
                    'purpose' => 'new',
                    'provider' => 'wata',
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

                $returnUrl = route('quick_buy.done', ['claimToken' => $claimToken], absolute: true);
                $failUrl = route('quick_buy.show', [], absolute: true);

                $request->session()->put('quick_buy_pw:'.$claimToken, $plainPassword);
                $request->session()->put('quick_buy_login:'.$claimToken, (int) $user->id);

                $link = $wata->createPaymentLink([
                    'type' => 'OneTime',
                    'amount' => (float) number_format($amountRub, 2, '.', ''),
                    'currency' => 'RUB',
                    'description' => $desc,
                    'orderId' => $orderId,
                    'successRedirectUrl' => $returnUrl,
                    'failRedirectUrl' => $failUrl,
                ]);

                $order->provider_link_id = $link['id'];
                $order->status = 'pending';
                $order->provider_payload = $link;
                $order->save();

                return response()->json([
                    'url' => $link['url'],
                    'doneUrl' => $returnUrl,
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'payment_create_failed'], 502);
        }
    }

    public function status(Request $request, string $orderId, WataH2hClient $wata): JsonResponse
    {
        $claim = trim((string) $request->query('claim', ''));
        if ($claim === '') {
            return response()->json(['error' => 'forbidden'], 403);
        }

        /** @var PaymentOrder|null $order */
        $order = PaymentOrder::query()
            ->where('order_id', $orderId)
            ->where('claim_token', $claim)
            ->first();
        if ($order === null) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if ($order->status === 'pending' && filled($order->provider_transaction_id)) {
            try {
                $remote = $wata->getTransaction((string) $order->provider_transaction_id);
                $remoteStatus = strtolower((string) ($remote['status'] ?? ''));
                if ($remoteStatus === 'paid' && $order->status !== 'paid') {
                    // Webhook мог опоздать — дождёмся его, не выдаём подписку здесь.
                } elseif ($remoteStatus === 'declined' && $order->status !== 'paid') {
                    $order->status = 'declined';
                    $order->declined_at = now();
                    $order->provider_payload = $remote;
                    $order->save();
                }
            } catch (\Throwable) {
                // Игнорируем временные ошибки WATA при поллинге.
            }
        }

        $subscriptionUrl = null;
        if ($order->status === 'paid' && $order->subscription_id !== null) {
            $subscription = Subscription::query()->find($order->subscription_id);
            if ($subscription !== null) {
                $subscriptionUrl = $subscription->shareableSubUrl();
            }
        }

        return response()->json([
            'status' => (string) $order->status,
            'subscriptionUrl' => $subscriptionUrl,
            'doneUrl' => route('quick_buy.done', ['claimToken' => $claim], absolute: false),
        ]);
    }

    public function done(Request $request, string $claimToken): View
    {
        if (strlen($claimToken) > 64) {
            throw new NotFoundHttpException;
        }

        /** @var PaymentOrder|null $order */
        $order = PaymentOrder::query()
            ->where('claim_token', $claimToken)
            ->with(['user', 'subscription'])
            ->first();
        if ($order === null) {
            throw new NotFoundHttpException;
        }

        $plainPassword = $request->session()->pull('quick_buy_pw:'.$claimToken);
        $loginUserId = (int) $request->session()->pull('quick_buy_login:'.$claimToken, 0);

        if ($loginUserId > 0 && $order->user_id === $loginUserId) {
            $user = User::query()->find($loginUserId);
            if ($user !== null && (! Auth::check() || Auth::id() !== $user->id)) {
                Auth::login($user, remember: true);
                $request->session()->regenerate();
            }
        }

        /** @var User|null $buyer */
        $buyer = $order->user;
        /** @var Subscription|null $subscription */
        $subscription = $order->subscription;

        $cabinetLoginUrl = $subscription !== null
            ? route('auth.via_token', ['token' => $subscription->token], absolute: false)
            : null;

        return view('quick-buy.done', [
            'order' => $order,
            'buyer' => $buyer,
            'subscription' => $subscription,
            'plainPassword' => is_string($plainPassword) ? $plainPassword : null,
            'cabinetLoginUrl' => $cabinetLoginUrl,
            'claimToken' => $claimToken,
            'shouldPoll' => $order->status !== 'paid' || ($order->status === 'paid' && $subscription === null),
        ]);
    }
}

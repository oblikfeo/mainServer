<?php

namespace App\Http\Controllers;

use App\Mail\QuickBuySubscriptionMail;
use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Services\QuickBuy\QuickCheckoutUserCreator;
use App\Services\Wata\WataH2hClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class QuickCheckoutController extends Controller
{
    public function show(): View
    {
        return view('quick-buy.index');
    }

    public function pay(Request $request, WataH2hClient $wata, QuickCheckoutUserCreator $userCreator): JsonResponse
    {
        if (trim((string) config('wata.access_token')) === '') {
            return response()->json(['error' => 'payments_not_configured'], 503);
        }

        $data = $request->validate([
            'plan' => ['required', 'string', 'max:32'],
            'period' => ['required', 'string', 'max:32'],
            'deviceData' => ['required', 'array'],
            'deviceData.browserAcceptHeader' => ['required', 'string', 'max:512'],
            'deviceData.browserLanguage' => ['required', 'string', 'max:32'],
            'deviceData.browserJavaEnabled' => ['required', 'boolean'],
            'deviceData.browserJavaScriptEnabled' => ['required', 'boolean'],
            'deviceData.browserColorDepth' => ['required', 'integer', 'min:1', 'max:64'],
            'deviceData.browserScreenHeight' => ['required', 'integer', 'min:1', 'max:10000'],
            'deviceData.browserScreenWidth' => ['required', 'integer', 'min:1', 'max:10000'],
            'deviceData.challengeWindowWidth' => ['required', 'integer', 'min:1', 'max:10000'],
            'deviceData.challengeWindowHeight' => ['required', 'integer', 'min:1', 'max:10000'],
            'deviceData.browserTZ' => ['required', 'integer', 'min:-12', 'max:14'],
            'deviceData.browserTZName' => ['required', 'string', 'max:32'],
            'deviceData.browserUserAgent' => ['required', 'string', 'max:512'],
        ]);

        $plan = (string) $data['plan'];
        $period = (string) $data['period'];
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

        try {
            return DB::transaction(function () use ($request, $wata, $userCreator, $plan, $period, $devices, $days, $quotaGb, $amountRub, $data): JsonResponse {
                [$user, $plainPassword] = $userCreator->create();

                $orderId = 'ord_'.(string) Str::ulid();
                $claimToken = Str::random(48);
                $desc = 'Подписка '.$plan.' · '.$period;

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
                $payload = [
                    'amount' => (float) number_format($amountRub, 2, '.', ''),
                    'currency' => 'RUB',
                    'description' => $desc,
                    'orderId' => $orderId,
                    'deviceData' => $data['deviceData'],
                    'ip' => (string) $request->ip(),
                    'returnUrl' => $returnUrl,
                ];

                $tx = $wata->createSbpTransaction($payload);

                $order->provider_transaction_id = $tx['transactionId'];
                $order->status = 'pending';
                $order->provider_payload = $tx;
                $order->save();

                $request->session()->put('quick_buy_pw:'.$claimToken, $plainPassword);
                $request->session()->put('quick_buy_login:'.$claimToken, (int) $user->id);

                return response()->json([
                    'orderId' => $orderId,
                    'claimToken' => $claimToken,
                    'amountRub' => $amountRub,
                    'description' => $desc,
                    'sbpLink' => $tx['sbpLink'],
                    'doneUrl' => $returnUrl,
                ]);
            });
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

    public function done(Request $request, string $claimToken): View|RedirectResponse
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
            'canSetEmail' => $buyer !== null && QuickCheckoutUserCreator::isAutogenEmail((string) $buyer->email),
            'shouldPoll' => $order->status !== 'paid' || ($order->status === 'paid' && $subscription === null),
        ]);
    }

    public function saveEmail(Request $request, string $claimToken): RedirectResponse
    {
        if (strlen($claimToken) > 64) {
            throw new NotFoundHttpException;
        }

        /** @var PaymentOrder|null $order */
        $order = PaymentOrder::query()
            ->where('claim_token', $claimToken)
            ->where('status', 'paid')
            ->with(['user', 'subscription'])
            ->first();
        if ($order === null || $order->user === null) {
            throw new NotFoundHttpException;
        }

        $data = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$order->user_id],
        ]);

        $user = $order->user;
        if (! QuickCheckoutUserCreator::isAutogenEmail((string) $user->email)) {
            return back()->with('status', 'email-already-set');
        }

        $email = (string) $data['email'];
        $user->email = $email;
        $user->email_verified_at = null;
        $user->save();

        $subscription = $order->subscription;
        if ($subscription !== null) {
            $brand = (string) config('marketing.brand_name', 'Надежда');
            $fromAddress = (string) (config('marketing.support_email') ?: config('mail.from.address', 'support@nadezhda.space'));
            $fromName = $brand.' · поддержка';

            Mail::to($email)->send(new QuickBuySubscriptionMail(
                brand: $brand,
                supportFromAddress: $fromAddress,
                supportFromName: $fromName,
                subscriptionUrl: $subscription->shareableSubUrl(),
                cabinetLoginUrl: route('auth.via_token', ['token' => $subscription->token], absolute: true),
            ));
        }

        return back()->with('status', 'email-saved');
    }
}

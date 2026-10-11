<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Account\AccountOrderFactory;
use App\Services\Account\AccountPricing;
use App\Services\Payments\PaymentDonePage;
use App\Services\Platega\PlategaClient;
use App\Services\QuickBuy\QuickCheckoutUserCreator;
use App\Services\Wata\WataH2hClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class QuickCheckoutController extends Controller
{
    public function show(): View
    {
        return view('quick-buy.index');
    }

    public function pay(Request $request, PlategaClient $platega, QuickCheckoutUserCreator $userCreator): JsonResponse
    {
        if (! $platega->isConfigured()) {
            return response()->json(['error' => 'payments_not_configured'], 503);
        }

        $data = $request->validate([
            'term' => ['required', 'string', 'max:16'],
            'devices' => ['nullable', 'integer', 'min:1', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
        ]);

        // Новый аккаунт: доп. устройства оплачиваются на тот же срок, что и подписка.
        $term = AccountPricing::terms()[(string) $data['term']] ?? null;
        if ($term === null) {
            return response()->json(['error' => 'unknown_tariff'], 422);
        }
        $devices = max(AccountPricing::baseDevices(), (int) ($data['devices'] ?? 0));
        try {
            $quote = AccountOrderFactory::quote(
                null,
                $term['key'],
                $devices,
                $devices > AccountPricing::baseDevices() ? $term['months'] : 0,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $email = strtolower(trim((string) $data['email']));
        $existing = User::query()->where('email', $email)->first();
        if ($existing !== null) {
            if (! $existing->isReleasableQuickBuyPlaceholder()) {
                throw ValidationException::withMessages([
                    'email' => 'Этот email уже занят. Войдите в кабинет или укажите другую почту.',
                ]);
            }
            $userCreator->releaseUnpaidPlaceholder($existing);
        }

        try {
            return DB::transaction(function () use ($request, $platega, $quote, $email): JsonResponse {
                $plainPassword = Str::password(12, symbols: false);
                $claimToken = Str::random(48);

                $order = AccountOrderFactory::create($quote, [
                    'claim_token' => $claimToken,
                    'user_id' => null,
                    'email' => $email,
                    'quick_buy_password' => Crypt::encryptString($plainPassword),
                ]);
                $orderId = (string) $order->order_id;
                $amountRub = (int) $order->amount_rub;
                $desc = (string) $order->description;

                $returnUrl = route('quick_buy.done', ['claimToken' => $claimToken], absolute: true);
                $failUrl = route('quick_buy.show', [], absolute: true);

                $request->session()->put('quick_buy_pw:'.$claimToken, $plainPassword);

                // Аккаунта ещё нет (создаём после оплаты) — для антифрода Platega передаём номер заказа.
                $tx = $platega->createTransaction(
                    amountRub: $amountRub,
                    description: $desc,
                    returnUrl: $returnUrl,
                    failedUrl: $failUrl,
                    payload: $orderId,
                    paymentMethod: null,
                    metadata: [
                        'userId' => $orderId,
                        'userName' => 'web-guest',
                    ],
                );

                $order->provider_transaction_id = (string) $tx['transactionId'];
                $order->status = 'pending';
                $order->provider_payload = $tx['raw'] ?? $tx;
                $order->save();

                return response()->json([
                    'url' => $tx['url'],
                    'doneUrl' => $returnUrl,
                ]);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'payment_create_failed'], 502);
        }
    }

    public function status(Request $request, string $orderId, PlategaClient $platega, WataH2hClient $wata): JsonResponse
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
            // Подписку здесь не выдаём ни при каком статусе — это делает только вебхук.
            try {
                if ((string) $order->provider === 'platega') {
                    $remote = $platega->getTransactionStatus((string) $order->provider_transaction_id);
                    $remoteStatus = strtoupper((string) ($remote['status'] ?? ''));
                    if ($remoteStatus === 'CANCELED' || $remoteStatus === 'CHARGEBACKED') {
                        $order->status = 'declined';
                        $order->declined_at = now();
                        $order->provider_payload = $remote;
                        $order->save();
                    }
                } else {
                    // Старые заказы WATA: провайдер отключён, но их страницы «спасибо» ещё открываются.
                    $remote = $wata->getTransaction((string) $order->provider_transaction_id);
                    $remoteStatus = strtolower((string) ($remote['status'] ?? ''));
                    if ($remoteStatus === 'declined') {
                        $order->status = 'declined';
                        $order->declined_at = now();
                        $order->provider_payload = $remote;
                        $order->save();
                    }
                }
            } catch (\Throwable) {
                // Временные ошибки провайдера при поллинге игнорируем.
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

    public function done(Request $request, string $claimToken, PaymentDonePage $donePage): View
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
        if ((! is_string($plainPassword) || $plainPassword === '') && filled($order->quick_buy_password)) {
            try {
                $plainPassword = Crypt::decryptString((string) $order->quick_buy_password);
            } catch (\Throwable) {
                $plainPassword = null;
            }
        }

        if ($order->status === 'paid' && filled($order->quick_buy_password)) {
            $order->quick_buy_password = null;
            $order->save();
        }

        $loginUserId = (int) $request->session()->pull('quick_buy_login:'.$claimToken, 0);
        $userId = (int) ($order->user_id ?? 0);
        if ($userId < 1) {
            $userId = $loginUserId;
        }
        if ($userId > 0) {
            $user = User::query()->find($userId);
            if ($user !== null && (! Auth::check() || Auth::id() !== $user->id)) {
                Auth::login($user, remember: true);
                $request->session()->regenerate();
            }
        }

        return view('quick-buy.done', $donePage->viewData(
            $order,
            is_string($plainPassword) ? $plainPassword : null,
        ));
    }
}

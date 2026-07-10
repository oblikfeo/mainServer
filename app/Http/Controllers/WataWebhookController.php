<?php

namespace App\Http\Controllers;

use App\Mail\QuickBuySubscriptionMail;
use App\Models\PaymentOrder;
use App\Models\Purchase;
use App\Models\User;
use App\Services\Referral\ReferralLinkBuilder;
use App\Services\Referral\ReferralRewardService;
use App\Services\Subscription\ApplySubscriptionRenewalPack;
use App\Services\Subscription\CreateDualBundleSubscription;
use App\Services\Telegram\TelegramOutreach;
use App\Services\Wata\WataH2hClient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WataWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        WataH2hClient $wata,
        CreateDualBundleSubscription $subs,
        ApplySubscriptionRenewalPack $renewals,
        ReferralRewardService $referralRewards,
        ReferralLinkBuilder $referralLinks,
        TelegramOutreach $telegramOutreach,
    ): Response {
        $raw = $request->getContent();
        $sig = (string) $request->header('X-Signature', '');
        if ($raw === '' || $sig === '') {
            Log::warning('WATA webhook: empty body or missing X-Signature');

            return response('', 400);
        }

        try {
            $pem = $wata->getWebhookPublicKeyPem();
            $ok = $this->verifySignature($raw, $sig, $pem);
            if (! $ok) {
                Log::warning('WATA webhook: bad signature');

                return response('', 403);
            }
        } catch (\Throwable $e) {
            Log::warning('WATA webhook: signature check failed: '.$e->getMessage());

            return response('', 503);
        }

        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            return response('', 200);
        }

        $orderId = isset($payload['orderId']) ? (string) $payload['orderId'] : '';
        $transactionId = isset($payload['transactionId']) ? (string) $payload['transactionId'] : '';
        $status = isset($payload['transactionStatus']) ? (string) $payload['transactionStatus'] : '';
        $kind = isset($payload['kind']) ? (string) $payload['kind'] : '';

        if ($orderId === '') {
            return response('', 200);
        }

        $order = PaymentOrder::query()->where('order_id', $orderId)->first();
        if (! $order) {
            Log::warning('WATA webhook: order not found: '.$orderId);

            return response('', 200);
        }

        $isPaid = $kind === 'Payment' && $status === 'Paid';
        $isDeclined = $kind === 'Payment' && $status === 'Declined';

        try {
            return DB::transaction(function () use (
                $order,
                $payload,
                $transactionId,
                $isPaid,
                $isDeclined,
                $subs,
                $renewals,
                $referralRewards,
                $referralLinks,
                $telegramOutreach,
            ): Response {
                /** @var PaymentOrder|null $locked */
                $locked = PaymentOrder::query()->whereKey($order->id)->lockForUpdate()->first();
                if (! $locked) {
                    return response('', 200);
                }

                $locked->provider_transaction_id = $transactionId !== '' ? $transactionId : $locked->provider_transaction_id;
                $locked->provider_payload = $payload;

                if ($isDeclined) {
                    if ($locked->status !== 'paid') {
                        $locked->status = 'declined';
                        $locked->declined_at = now();
                    }
                    $locked->save();

                    return response('', 200);
                }

                if (! $isPaid) {
                    if ($locked->status === 'created') {
                        $locked->status = 'pending';
                    }
                    $locked->save();

                    return response('', 200);
                }

                // Paid: идемпотентно выполняем выдачу один раз.
                if ($locked->status === 'paid') {
                    return response('', 200);
                }

                $locked->status = 'paid';
                $locked->paid_at = $locked->paid_at ?? now();
                $locked->save();

                $buyer = User::query()->whereKey((int) $locked->user_id)->first();
                $purpose = (string) ($locked->purpose ?? 'new');

                if ($purpose === 'renew') {
                    $targetId = (int) $locked->subscription_id;
                    if ($targetId < 1) {
                        Log::error('WATA webhook: renew order without subscription_id: '.$locked->order_id);

                        return response('', 500);
                    }
                    $renewed = $renewals->apply(
                        $targetId,
                        (int) $locked->days,
                        (int) $locked->quota_gb,
                        (int) $locked->devices,
                        (string) ($locked->tariff_plan ?? ''),
                    );
                    $expMs = (int) $renewed->expiry_ms;
                } elseif ($purpose === 'extra_device') {
                    $targetId = (int) $locked->subscription_id;
                    if ($targetId < 1) {
                        Log::error('WATA webhook: extra_device order without subscription_id: '.$locked->order_id);

                        return response('', 500);
                    }
                    $addDevices = (int) $locked->devices;
                    if ($addDevices < 1) {
                        Log::error('WATA webhook: extra_device order without devices: '.$locked->order_id);

                        return response('', 500);
                    }
                    $renewed = $renewals->apply($targetId, 0, 0, $addDevices);
                    $expMs = (int) $renewed->expiry_ms;
                } else {
                    $result = $subs->create(
                        (int) $locked->devices,
                        (int) $locked->days,
                        (int) $locked->quota_gb,
                        (int) $locked->user_id
                    );

                    $locked->subscription_id = $result->subscription->id;
                    $locked->save();

                    if ($buyer !== null) {
                        $referralRewards->consumeUserCreditsOnNewSubscription($buyer, $result->subscription);
                    }
                    $renewed = $result->subscription;
                    $expMs = (int) $renewed->expiry_ms;
                }

                $purchase = Purchase::query()->create([
                    'user_id' => (int) $locked->user_id,
                    'amount_rub' => (int) $locked->amount_rub,
                    'currency' => (string) $locked->currency,
                    'paid_at' => $locked->paid_at ?? now(),
                    'description' => (string) ($locked->description ?? 'Оплата'),
                ]);

                if ($buyer !== null) {
                    $referralRewards->onPurchaseRecorded($purchase);
                }

                if ($buyer !== null) {
                    $newDate = $expMs <= 0
                        ? 'без ограничения срока'
                        : Carbon::createFromTimestampMs($expMs)->timezone((string) config('app.timezone'))->format('d.m.Y');
                    $telegramOutreach->notifyUserIfEligible($buyer, 'billing_paid_ok', [
                        'amount' => (string) (int) $locked->amount_rub,
                        'new_date' => $newDate,
                    ]);
                }

                if (filled($locked->claim_token) && $buyer !== null) {
                    try {
                        $brand = (string) config('marketing.brand_name', 'Надежда');
                        $fromAddress = (string) (config('marketing.support_email') ?: config('mail.from.address', 'support@nadezhda.space'));
                        Mail::to($buyer->email)->send(new QuickBuySubscriptionMail(
                            brand: $brand,
                            supportFromAddress: $fromAddress,
                            supportFromName: $brand.' · поддержка',
                            subscriptionUrl: $renewed->shareableSubUrl(),
                            cabinetLoginUrl: route('auth.via_token', ['token' => $renewed->token], absolute: true),
                            referralLink: $referralLinks->forUser($buyer),
                        ));
                    } catch (\Throwable $e) {
                        Log::warning('WATA webhook: quick buy email failed: '.$e->getMessage());
                    }
                }

                return response('', 200);
            });
        } catch (\Throwable $e) {
            // Для post-payment вебхука WATA будет ретраить до 32 часов — это полезно, если панель/БД временно упала.
            Log::error('WATA webhook: paid handler failed: '.$e->getMessage());

            return response('', 500);
        }
    }

    private function verifySignature(string $rawJson, string $signatureBase64, string $publicKeyPem): bool
    {
        $key = openssl_get_publickey($publicKeyPem);
        if ($key === false) {
            return false;
        }
        $sigBytes = base64_decode($signatureBase64, true);
        if ($sigBytes === false) {
            return false;
        }
        $result = openssl_verify($rawJson, $sigBytes, $key, OPENSSL_ALGO_SHA512);
        openssl_free_key($key);

        return $result === 1;
    }
}


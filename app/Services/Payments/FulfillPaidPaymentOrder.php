<?php

namespace App\Services\Payments;

use App\Mail\QuickBuySubscriptionMail;
use App\Models\PaymentOrder;
use App\Models\Purchase;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Referral\ReferralLinkBuilder;
use App\Services\Referral\ReferralRewardService;
use App\Services\Subscription\ApplySubscriptionRenewalPack;
use App\Services\Subscription\CreateDualBundleSubscription;
use App\Services\Telegram\TelegramOutreach;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Выдача подписки после успешной оплаты (Wata, Platega и др.).
 * Вызывать только когда заказ ещё не paid; внутри ставит paid и выполняет side-effects.
 */
final class FulfillPaidPaymentOrder
{
    public function __construct(
        private readonly CreateDualBundleSubscription $subs,
        private readonly ApplySubscriptionRenewalPack $renewals,
        private readonly ReferralRewardService $referralRewards,
        private readonly ReferralLinkBuilder $referralLinks,
        private readonly TelegramOutreach $telegramOutreach,
    ) {}

    /**
     * @return array{subscription: Subscription, expiry_ms: int}
     */
    public function fulfill(PaymentOrder $order): array
    {
        if ($order->status === 'paid') {
            $sub = $order->subscription_id !== null
                ? Subscription::query()->find((int) $order->subscription_id)
                : null;

            return [
                'subscription' => $sub ?? new Subscription,
                'expiry_ms' => $sub !== null ? (int) $sub->expiry_ms : 0,
            ];
        }

        $order->status = 'paid';
        $order->paid_at = $order->paid_at ?? now();
        $order->save();

        $buyer = User::query()->whereKey((int) $order->user_id)->first();
        $purpose = (string) ($order->purpose ?? 'new');

        if ($purpose === 'renew') {
            $targetId = (int) $order->subscription_id;
            if ($targetId < 1) {
                throw new \RuntimeException('renew order without subscription_id: '.$order->order_id);
            }
            $renewed = $this->renewals->apply(
                $targetId,
                (int) $order->days,
                (int) $order->quota_gb,
                (int) $order->devices,
                (string) ($order->tariff_plan ?? ''),
            );
            $expMs = (int) $renewed->expiry_ms;
        } elseif ($purpose === 'extra_device') {
            $targetId = (int) $order->subscription_id;
            if ($targetId < 1) {
                throw new \RuntimeException('extra_device order without subscription_id: '.$order->order_id);
            }
            $addDevices = (int) $order->devices;
            if ($addDevices < 1) {
                throw new \RuntimeException('extra_device order without devices: '.$order->order_id);
            }
            $renewed = $this->renewals->apply($targetId, 0, 0, $addDevices);
            $expMs = (int) $renewed->expiry_ms;
        } else {
            $result = $this->subs->create(
                (int) $order->devices,
                (int) $order->days,
                (int) $order->quota_gb,
                (int) $order->user_id
            );

            $order->subscription_id = $result->subscription->id;
            $order->save();

            if ($buyer !== null) {
                $this->referralRewards->consumeUserCreditsOnNewSubscription($buyer, $result->subscription);
            }
            $renewed = $result->subscription;
            $expMs = (int) $renewed->expiry_ms;
        }

        $purchase = Purchase::query()->create([
            'user_id' => (int) $order->user_id,
            'amount_rub' => (int) $order->amount_rub,
            'currency' => (string) $order->currency,
            'paid_at' => $order->paid_at ?? now(),
            'description' => (string) ($order->description ?? 'Оплата'),
        ]);

        if ($buyer !== null) {
            $this->referralRewards->onPurchaseRecorded($purchase);
        }

        if ($buyer !== null) {
            $newDate = $expMs <= 0
                ? 'без ограничения срока'
                : Carbon::createFromTimestampMs($expMs)->timezone((string) config('app.timezone'))->format('d.m.Y');
            $this->telegramOutreach->notifyUserIfEligible($buyer, 'billing_paid_ok', [
                'amount' => (string) (int) $order->amount_rub,
                'new_date' => $newDate,
            ]);
        }

        if (filled($order->claim_token) && $buyer !== null) {
            try {
                $brand = (string) config('marketing.brand_name', 'Надежда');
                $fromAddress = (string) (config('marketing.support_email') ?: config('mail.from.address', 'support@nadezhda.space'));
                Mail::to($buyer->email)->send(new QuickBuySubscriptionMail(
                    brand: $brand,
                    supportFromAddress: $fromAddress,
                    supportFromName: $brand.' · поддержка',
                    subscriptionUrl: $renewed->shareableSubUrl(),
                    cabinetLoginUrl: route('auth.via_token', ['token' => $renewed->token], absolute: true),
                    referralLink: $this->referralLinks->forUser($buyer),
                ));
            } catch (\Throwable $e) {
                Log::warning('payment fulfill: quick buy email failed: '.$e->getMessage());
            }
        }

        return [
            'subscription' => $renewed,
            'expiry_ms' => $expMs,
        ];
    }
}

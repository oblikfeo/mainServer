<?php

namespace App\Services\Payments;

use App\Mail\QuickBuySubscriptionMail;
use App\Models\PaymentOrder;
use App\Models\Purchase;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Promo\PromoCodeService;
use App\Services\QuickBuy\QuickCheckoutUserCreator;
use App\Services\Referral\ReferralLinkBuilder;
use App\Services\Referral\ReferralRewardService;
use App\Services\Account\AccountPricing;
use App\Services\Account\AccountSubscription;
use App\Services\Telegram\TelegramOutreach;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Выдача подписки после успешной оплаты (Wata, Platega и др.).
 * Вызывать только когда заказ ещё не paid; внутри ставит paid и выполняет side-effects.
 */
final class FulfillPaidPaymentOrder
{
    public function __construct(
        private readonly AccountSubscription $account,
        private readonly ReferralRewardService $referralRewards,
        private readonly ReferralLinkBuilder $referralLinks,
        private readonly TelegramOutreach $telegramOutreach,
        private readonly QuickCheckoutUserCreator $quickBuyUsers,
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

        $buyer = $this->ensureBuyer($order);
        $purpose = (string) ($order->purpose ?? 'new');

        if ($buyer === null || (int) $order->user_id < 1) {
            throw new \RuntimeException('paid order without user: '.$order->order_id);
        }

        // «Аккаунт = подписка»: любая оплата продлевает единственную подписку аккаунта (или создаёт первую).
        [$days, $extraDevices, $deviceDays] = $this->purchaseParts($order, $purpose);
        // Подарок по промокоду — к первой оплате, в которой есть срок подписки (докупка одних устройств его не тратит).
        $bonusDays = $days > 0 ? PromoCodeService::takeFirstPaymentBonus($buyer) : 0;
        $result = $this->account->applyPurchase($buyer, $days + $bonusDays, $extraDevices, $deviceDays, (int) $order->id);

        $renewed = $result['subscription'];
        $order->subscription_id = $renewed->id;
        if ($bonusDays > 0) {
            $order->bonus_days = $bonusDays;
            $order->description = trim((string) $order->description).' + '.$bonusDays.' дн. в подарок ('.$buyer->registration_promo_code.')';
        }
        $order->save();

        if ($result['created']) {
            $this->referralRewards->consumeUserCreditsOnNewSubscription($buyer, $renewed);
        }
        $expMs = (int) $renewed->expiry_ms;

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

    /**
     * Что куплено заказом: [дни подписки, сколько доп. устройств, на сколько дней каждое].
     * Цели new / renew / extra_device — заказы старой схемы, созданные до перехода.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    private function purchaseParts(PaymentOrder $order, string $purpose): array
    {
        $days = max(0, (int) $order->days);
        $devices = max(0, (int) $order->devices);
        $monthDays = AccountPricing::deviceMonthDays();

        return match ($purpose) {
            'account' => [$days, $devices, max(0, (int) $order->device_months) * $monthDays],
            // Старая «Семейная» (5 устройств) = базовые + доп. слоты на тот же срок.
            'new' => [$days, max(0, $devices - AccountPricing::baseDevices()), $days],
            'renew' => [$days, $devices, $days],
            'extra_device' => [0, max(1, $devices), max($monthDays, $days)],
            default => throw new \RuntimeException('unknown order purpose «'.$purpose.'»: '.$order->order_id),
        };
    }

    private function ensureBuyer(PaymentOrder $order): ?User
    {
        if ((int) $order->user_id > 0) {
            return User::query()->whereKey((int) $order->user_id)->first();
        }

        $email = strtolower(trim((string) ($order->email ?? '')));
        if ($email === '') {
            throw new \RuntimeException('payment order has no user_id and no email: '.$order->order_id);
        }

        $existing = User::query()->where('email', $email)->first();
        if ($existing !== null) {
            $order->user_id = $existing->id;
            $order->save();

            return $existing;
        }

        $plain = null;
        if (filled($order->quick_buy_password)) {
            try {
                $plain = Crypt::decryptString((string) $order->quick_buy_password);
            } catch (\Throwable) {
                $plain = null;
            }
        }

        [$user] = $this->quickBuyUsers->create($email, $plain);
        $order->user_id = $user->id;
        $order->save();

        return $user;
    }
}

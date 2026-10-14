<?php

namespace App\Services\Account;

use App\Models\Subscription;
use App\Models\User;
use App\Services\Subscription\CreateDualBundleSubscription;
use App\Services\Subscription\SubscriptionCalendarExtension;
use App\Services\Xui\XuiSubscriptionLimitIpSync;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * «Аккаунт = подписка»: у аккаунта ровно одна подписка. Все пути (оплата, триал, админка, бот)
 * получают, создают и продлевают её только через этот сервис — вторая подписка появиться не может.
 */
final class AccountSubscription
{
    public function __construct(
        private readonly CreateDualBundleSubscription $creator,
        private readonly SubscriptionCalendarExtension $calendar,
        private readonly XuiSubscriptionLimitIpSync $limitIpSync,
    ) {}

    public static function forUser(User|int|null $user): ?Subscription
    {
        $id = $user instanceof User ? (int) $user->id : (int) $user;
        if ($id < 1) {
            return null;
        }

        return Subscription::query()->where('user_id', $id)->orderByDesc('id')->first();
    }

    /**
     * Применить оплаченную покупку: продлить (или создать) подписку аккаунта и оплатить доп. устройства.
     *
     * @param  int  $days  дни подписки (0 — не продлевать)
     * @param  int  $extraDevices  сколько доп. устройств оплачено
     * @param  int  $deviceDays  на сколько дней оплачено каждое из них
     * @return array{subscription: Subscription, created: bool}
     */
    public function applyPurchase(User $user, int $days, int $extraDevices, int $deviceDays, ?int $paymentOrderId = null): array
    {
        $days = max(0, $days);
        $extraDevices = max(0, $extraDevices);
        $deviceDays = max(0, $deviceDays);

        return DB::transaction(function () use ($user, $days, $extraDevices, $deviceDays, $paymentOrderId): array {
            $sub = Subscription::query()->where('user_id', $user->id)->orderByDesc('id')->lockForUpdate()->first();
            $created = false;

            if ($sub === null) {
                if ($days < 1) {
                    throw new RuntimeException('Нет подписки: нельзя оплатить только устройства (user '.$user->id.')');
                }
                $sub = $this->creator->create(
                    devices: AccountPricing::baseDevices(),
                    days: $days,
                    quotaGb: 0,
                    userId: (int) $user->id,
                    unlimitedTraffic: true,
                    expiryMsOverride: (int) (now()->addDays($days)->getTimestamp() * 1000),
                )->subscription;
                $created = true;
            } else {
                // Остатки слотов фиксируем до сдвига срока: простой без подписки с них не списывается.
                DeviceSlots::settle($sub);

                if ($days > 0) {
                    $this->calendar->addCalendarDays($sub, $days);
                    $sub->refresh();
                    if ($sub->is_trial) {
                        $sub->is_trial = false;
                        $sub->devices = max((int) $sub->devices, AccountPricing::baseDevices());
                        $sub->save();
                    }
                }
            }

            if ($extraDevices > 0 && $deviceDays > 0) {
                if ($days > 0) {
                    // Продление: сначала продлеваем уже оплаченные доп. устройства, остальное — новые.
                    $extended = DeviceSlots::extend($sub, $extraDevices, $deviceDays);
                    DeviceSlots::add($sub, $extraDevices - $extended, $deviceDays, $paymentOrderId);
                } else {
                    // Докупка без продления: только новые устройства, старые дорабатывают свой срок.
                    DeviceSlots::add($sub, $extraDevices, $deviceDays, $paymentOrderId);
                }
            }

            $sub = $sub->fresh();
            $this->limitIpSync->syncForSubscription($sub);

            return ['subscription' => $sub, 'created' => $created];
        });
    }

    /**
     * Пробный или подарочный доступ на $hours часов. Если подписка у аккаунта уже есть — продлеваем её же.
     *
     * @return array{subscription: Subscription, created: bool}
     */
    public function grantHours(User $user, int $hours, int $devices, int $quotaGb, bool $isTrial, ?string $promoCode = null): array
    {
        $hours = max(1, $hours);

        return DB::transaction(function () use ($user, $hours, $devices, $quotaGb, $isTrial, $promoCode): array {
            $sub = Subscription::query()->where('user_id', $user->id)->orderByDesc('id')->lockForUpdate()->first();
            if ($sub === null) {
                $expiryMs = (int) (now()->addHours($hours)->getTimestamp() * 1000);
                $sub = $this->creator->create(
                    devices: $devices,
                    days: 0,
                    quotaGb: $quotaGb,
                    userId: (int) $user->id,
                    unlimitedTraffic: false,
                    unlimitedTime: false,
                    isTrial: $isTrial,
                    expiryMsOverride: $expiryMs,
                    promoCode: $promoCode,
                )->subscription;

                return ['subscription' => $sub, 'created' => true];
            }

            DeviceSlots::settle($sub);
            $this->calendar->addCalendarDays($sub, $hours / 24);
            $sub->refresh();
            if ($promoCode !== null && $promoCode !== '') {
                $sub->promo_code = $promoCode;
                $sub->save();
            }

            return ['subscription' => $sub->fresh(), 'created' => false];
        });
    }
}

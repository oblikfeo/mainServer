<?php

namespace App\Services\Subscription;

use App\Models\User;
use App\Services\Xui\XuiPanelException;

/**
 * Пробная подписка на тех же узлах, что и платная (CreateDualBundleSubscription), с лимитами из trial_subscription.
 */
final class TrialSubscriptionIssuer
{
    public function __construct(
        private readonly CreateDualBundleSubscription $createDual,
    ) {}

    /**
     * Самовыдача из ЛК: реферальные «слоты» фиксированной длительности, иначе базовые часы + referral_test_credit_hours.
     *
     * @throws XuiPanelException
     */
    public function issueFromCabinet(User $user, bool $referralInviteeSlot): CreatedSubscriptionResult
    {
        $cap = max(1, (int) config('trial_subscription.cabinet_hours_cap', 48));
        $baseHours = max(1, (int) config('trial_subscription.hours', 8));

        if ($referralInviteeSlot) {
            $hours = max(1, min($cap, $baseHours));
        } else {
            $credit = (int) ($user->referral_test_credit_hours ?? 0);
            $hours = max(1, min($cap, $baseHours + $credit));
            if ($credit !== 0) {
                $user->forceFill(['referral_test_credit_hours' => 0])->save();
            }
        }

        return $this->issueWithHours($user, $hours);
    }

    /**
     * Выдача из админки: часы задаются явно (или дефолт из конфига).
     *
     * @throws XuiPanelException
     */
    public function issueFromAdmin(User $user, ?int $hours = null): CreatedSubscriptionResult
    {
        $max = max(48, (int) config('trial_subscription.admin_hours_max', 8760));
        $h = $hours ?? max(1, (int) config('trial_subscription.hours', 8));

        return $this->issueWithHours($user, max(1, min($max, $h)));
    }

    /**
     * @throws XuiPanelException
     */
    private function issueWithHours(User $user, int $hours): CreatedSubscriptionResult
    {
        $devices = max(0, (int) config('trial_subscription.devices', 1));
        $quotaGb = max(1, (int) config('trial_subscription.quota_gb', 5));
        $expiryMs = (int) (now()->addHours($hours)->getTimestamp() * 1000);

        return $this->createDual->create(
            devices: $devices,
            days: 0,
            quotaGb: $quotaGb,
            userId: (int) $user->id,
            unlimitedTraffic: false,
            unlimitedTime: false,
            isTrial: true,
            expiryMsOverride: $expiryMs,
        );
    }
}

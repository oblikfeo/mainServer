<?php

namespace App\Services\Subscription;

use App\Models\User;
use App\Services\Account\AccountSubscription;
use App\Services\Xui\XuiPanelException;

/**
 * Пробный доступ с лимитами из trial_subscription. «Аккаунт = подписка»: если подписка у аккаунта
 * уже есть, пробные часы прибавляются к ней же, вторая не создаётся.
 */
final class TrialSubscriptionIssuer
{
    public function __construct(
        private readonly AccountSubscription $account,
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
     * Промокод при регистрации: длительность в календарных днях, без cabinet cap.
     *
     * @throws XuiPanelException
     */
    public function issueFromPromo(User $user, int $days, string $promoCode, ?int $devices = null): CreatedSubscriptionResult
    {
        $hours = max(24, $days * 24);

        return $this->issueWithHours($user, $hours, $promoCode, $devices);
    }

    /**
     * @throws XuiPanelException
     */
    private function issueWithHours(User $user, int $hours, ?string $promoCode = null, ?int $devices = null): CreatedSubscriptionResult
    {
        $devices ??= max(0, (int) config('trial_subscription.devices', 1));
        $quotaGb = max(1, (int) config('trial_subscription.quota_gb', 5));

        $subscription = $this->account->grantHours($user, $hours, $devices, $quotaGb, isTrial: true, promoCode: $promoCode)['subscription'];

        return new CreatedSubscriptionResult(
            $subscription,
            $subscription->shareableSubUrl(),
            '',
            '',
            null,
        );
    }
}

<?php

namespace App\Services\Referral;

use App\Models\Purchase;
use App\Models\ReferralGrant;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Subscription\SubscriptionCalendarExtension;
use App\Services\Xui\XuiSubscriptionLimitIpSync;
use App\Services\Xui\XuiSubscriptionQuotaSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ReferralRewardService
{
    public function __construct(
        private readonly SubscriptionCalendarExtension $calendarExtension,
        private readonly XuiSubscriptionQuotaSync $quotaSync,
        private readonly XuiSubscriptionLimitIpSync $limitIpSync,
        private readonly ReferralMetrics $metrics,
    ) {}

    public static function grantKeyFirstRegReferrer(int $referrerId): string
    {
        return 'first_reg_referrer:'.$referrerId;
    }

    public static function grantKeyFirstRegRefereeCredit(int $refereeId): string
    {
        return 'first_reg_referee_credit:'.$refereeId;
    }

    public static function grantKeyFirstPaymentPair(int $referrerId, int $refereeId): string
    {
        return 'first_payment_pair:'.$referrerId.':'.$refereeId;
    }

    public static function grantKeyMilestoneDevices(int $referrerId): string
    {
        return 'milestone_devices:'.$referrerId;
    }

    public static function grantKeyMilestoneTraffic(int $referrerId): string
    {
        return 'milestone_traffic:'.$referrerId;
    }

    /**
     * После регистрации пользователя, пришедшего по реф-ссылке.
     */
    public function onReferredUserRegistered(User $referee): void
    {
        if ($referee->referred_by === null) {
            return;
        }

        $referrerId = (int) $referee->referred_by;
        if ($referrerId < 1 || $referrerId === (int) $referee->id) {
            return;
        }

        try {
            DB::transaction(function () use ($referee, $referrerId): void {
                $count = (int) User::query()->where('referred_by', $referrerId)->lockForUpdate()->count();
                if ($count !== 1) {
                    return;
                }

                $this->grantFirstRegistration((int) $referee->id, $referrerId);
            });
        } catch (\Throwable $e) {
            Log::error('referral.first_registration_failed', [
                'message' => $e->getMessage(),
                'referee_id' => $referee->id,
            ]);
        }
    }

    private function grantFirstRegistration(int $refereeId, int $referrerId): void
    {
        $kRef = self::grantKeyFirstRegReferrer($referrerId);
        if (! ReferralGrant::query()->where('grant_key', $kRef)->exists()) {
            $referrer = User::query()->whereKey($referrerId)->first();
            if ($referrer === null) {
                return;
            }

            $days = (float) config('referral.first_registration_referrer_days', 1);
            $this->addCalendarDaysToUser($referrer, $days);

            ReferralGrant::query()->create([
                'grant_key' => $kRef,
                'kind' => ReferralGrant::KIND_FIRST_REG_REFERRER_DAYS,
                'beneficiary_user_id' => $referrerId,
                'referee_user_id' => $refereeId,
                'purchase_id' => null,
                'meta' => ['days' => $days],
            ]);
        }

        $kRee = self::grantKeyFirstRegRefereeCredit($refereeId);
        if (! ReferralGrant::query()->where('grant_key', $kRee)->exists()) {
            $referee = User::query()->whereKey($refereeId)->lockForUpdate()->first();
            if ($referee === null) {
                return;
            }

            $hours = (int) config('referral.first_registration_referee_test_hours', 8);
            $referee->referral_test_credit_hours = (int) $referee->referral_test_credit_hours + $hours;
            $referee->save();

            ReferralGrant::query()->create([
                'grant_key' => $kRee,
                'kind' => ReferralGrant::KIND_FIRST_REG_REFEREE_TEST_CREDIT,
                'beneficiary_user_id' => $refereeId,
                'referee_user_id' => $refereeId,
                'purchase_id' => null,
                'meta' => ['hours' => $hours],
            ]);
        }
    }

    /**
     * После записи оплаты (покупатель — реферал или любой пользователь).
     */
    public function onPurchaseRecorded(Purchase $purchase): void
    {
        $buyer = User::query()->whereKey($purchase->user_id)->first();
        if ($buyer === null || $buyer->referred_by === null) {
            return;
        }

        $referrerId = (int) $buyer->referred_by;
        $refereeId = (int) $buyer->id;
        if ($referrerId < 1) {
            return;
        }

        try {
            DB::transaction(function () use ($purchase, $referrerId, $refereeId): void {
                $firstPurchaseId = Purchase::query()
                    ->where('user_id', $refereeId)
                    ->orderBy('id')
                    ->value('id');

                if ((int) $firstPurchaseId !== (int) $purchase->id) {
                    $this->refreshMilestoneGrants($referrerId);

                    return;
                }

                $k = self::grantKeyFirstPaymentPair($referrerId, $refereeId);
                if (! ReferralGrant::query()->where('grant_key', $k)->exists()) {
                    $pairDays = (float) config('referral.first_payment_pair_days', 7);
                    $referrer = User::query()->whereKey($referrerId)->first();
                    if ($referrer !== null) {
                        $this->addCalendarDaysToUser($referrer, $pairDays);
                    }
                    $buyerFresh = User::query()->whereKey($refereeId)->first();
                    if ($buyerFresh !== null) {
                        $this->addCalendarDaysToUser($buyerFresh, $pairDays);
                    }

                    ReferralGrant::query()->create([
                        'grant_key' => $k,
                        'kind' => ReferralGrant::KIND_FIRST_PAYMENT_PAIR,
                        'beneficiary_user_id' => $referrerId,
                        'referee_user_id' => $refereeId,
                        'purchase_id' => (int) $purchase->id,
                        'meta' => ['pair_days' => $pairDays],
                    ]);
                }

                $this->refreshMilestoneGrants($referrerId);
            });
        } catch (\Throwable $e) {
            Log::error('referral.first_purchase_pair_failed', [
                'message' => $e->getMessage(),
                'purchase_id' => $purchase->id,
            ]);
        }
    }

    public function refreshMilestoneGrants(int $referrerId): void
    {
        $n = $this->metrics->countReferralsWithActiveSubscription($referrerId);
        $mDev = (int) config('referral.active_paid_milestone_devices', 4);
        $mTr = (int) config('referral.active_paid_milestone_traffic', 10);
        $maxDev = (int) config('referral.max_subscription_devices', 5);

        if ($n >= $mDev) {
            $this->grantMilestoneDeviceIfMissing($referrerId);
        }
        if ($n >= $mTr) {
            $this->grantMilestoneUnlimitedIfMissing($referrerId);
        }
    }

    private function grantMilestoneDeviceIfMissing(int $referrerId): void
    {
        $k = self::grantKeyMilestoneDevices($referrerId);
        if (ReferralGrant::query()->where('grant_key', $k)->exists()) {
            return;
        }

        $maxDev = (int) config('referral.max_subscription_devices', 5);

        $user = User::query()->whereKey($referrerId)->lockForUpdate()->first();
        if ($user === null) {
            return;
        }

        $sub = $this->primaryActiveSubscription($user);
        if ($sub === null) {
            $user->referral_pending_extra_devices = (int) $user->referral_pending_extra_devices + 1;
            $user->save();
        } else {
            $newDevices = min($maxDev, (int) $sub->devices + 1);
            if ($newDevices > (int) $sub->devices) {
                $sub->devices = $newDevices;
                $sub->save();
                $this->limitIpSync->syncForSubscription($sub);
            }
        }

        ReferralGrant::query()->create([
            'grant_key' => $k,
            'kind' => ReferralGrant::KIND_MILESTONE_EXTRA_DEVICE,
            'beneficiary_user_id' => $referrerId,
            'referee_user_id' => null,
            'purchase_id' => null,
            'meta' => null,
        ]);
    }

    private function grantMilestoneUnlimitedIfMissing(int $referrerId): void
    {
        $k = self::grantKeyMilestoneTraffic($referrerId);
        if (ReferralGrant::query()->where('grant_key', $k)->exists()) {
            return;
        }

        $user = User::query()->whereKey($referrerId)->lockForUpdate()->first();
        if ($user === null) {
            return;
        }

        $sub = $this->primaryActiveSubscription($user);
        if ($sub === null) {
            $user->referral_pending_unlimited_traffic = true;
            $user->save();
        } else {
            if ((int) $sub->quota_gb !== 0) {
                $sub->quota_gb = 0;
                $sub->save();
                $this->quotaSync->syncForSubscription($sub);
            }
        }

        ReferralGrant::query()->create([
            'grant_key' => $k,
            'kind' => ReferralGrant::KIND_MILESTONE_UNLIMITED_TRAFFIC,
            'beneficiary_user_id' => $referrerId,
            'referee_user_id' => null,
            'purchase_id' => null,
            'meta' => null,
        ]);
    }

    public function addCalendarDaysToUser(User $user, float $days): void
    {
        if ($days <= 0) {
            return;
        }

        $sub = $this->primaryActiveSubscription($user);
        if ($sub !== null) {
            $this->calendarExtension->addCalendarDays($sub, $days);

            return;
        }

        $user->referral_subscription_credit_days = round((float) $user->referral_subscription_credit_days + $days, 2);
        $user->save();
    }

    public function consumeUserCreditsOnNewSubscription(User $user, Subscription $newSubscription): void
    {
        $credits = (float) $user->referral_subscription_credit_days;
        $changed = false;
        if ($credits > 0) {
            $this->calendarExtension->addCalendarDays($newSubscription, $credits);
            $user->referral_subscription_credit_days = 0;
            $changed = true;
        }

        $devExtra = (int) $user->referral_pending_extra_devices;
        if ($devExtra > 0) {
            $max = (int) config('referral.max_subscription_devices', 5);
            $newSubscription->refresh();
            $d = min($max, (int) $newSubscription->devices + $devExtra);
            $newSubscription->devices = $d;
            $newSubscription->save();
            $user->referral_pending_extra_devices = 0;
            $this->limitIpSync->syncForSubscription($newSubscription);
            $changed = true;
        }

        if ((bool) $user->referral_pending_unlimited_traffic) {
            if ((int) $newSubscription->quota_gb !== 0) {
                $newSubscription->quota_gb = 0;
                $newSubscription->save();
                $this->quotaSync->syncForSubscription($newSubscription);
            }
            $user->referral_pending_unlimited_traffic = false;
            $changed = true;
        }

        if ($changed) {
            $user->save();
        }
    }

    public function primaryActiveSubscription(User $user): ?Subscription
    {
        $nowMs = $this->metrics->nowMs();

        return Subscription::query()
            ->where('user_id', $user->id)
            ->where(function ($q) use ($nowMs) {
                $q->where('expiry_ms', '<=', 0)
                    ->orWhere('expiry_ms', '>', $nowMs);
            })
            ->orderByDesc('id')
            ->first();
    }
}

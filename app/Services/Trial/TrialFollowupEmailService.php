<?php

namespace App\Services\Trial;

use App\Mail\TrialFollowupMail;
use App\Models\User;
use App\Services\Subscription\TrialSubscriptionIssuer;
use App\Services\Xui\XuiPanelException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class TrialFollowupEmailService
{
    public function __construct(
        private readonly TrialSubscriptionIssuer $trialIssuer,
    ) {}

    /**
     * @return list<User>
     */
    public function findEligibleUsers(): array
    {
        $delayHours = max(1, (int) config('trial_subscription.followup_after_expiry_hours', 24));
        $cutoff = now()->subHours($delayHours);

        $candidates = User::query()
            ->whereNotNull('email_verified_at')
            ->whereNull('trial_followup_email_sent_at')
            ->whereHas('subscriptions', fn ($s) => $s->where('is_trial', true))
            ->whereDoesntHave('paymentOrders', fn ($po) => $po->where('status', 'paid'))
            ->whereDoesntHave('subscriptions', fn ($s) => $s->where('is_trial', false))
            ->orderBy('id')
            ->get();

        $eligible = [];

        foreach ($candidates as $user) {
            if ($this->isEligible($user, $cutoff)) {
                $eligible[] = $user;
            }
        }

        return $eligible;
    }

    public function isEligible(User $user, ?Carbon $cutoff = null): bool
    {
        if (! (bool) config('trial_subscription.followup_enabled', true)) {
            return false;
        }

        if ($user->email_verified_at === null || $user->trial_followup_email_sent_at !== null) {
            return false;
        }

        if (! $user->hadSubscriptionTrial() || $user->hasEverPaid() || $user->hasActiveTrialAccess()) {
            return false;
        }

        $endedAt = $user->latestSubscriptionTrialEndedAt();
        if ($endedAt === null) {
            return false;
        }

        $eligibleAfter = $this->eligibleTrialsEndingAfter();
        if ($eligibleAfter !== null && $endedAt->lt($eligibleAfter)) {
            return false;
        }

        $delayHours = max(1, (int) config('trial_subscription.followup_after_expiry_hours', 24));
        $cutoff ??= now()->subHours($delayHours);

        if ($endedAt->gt($cutoff)) {
            return false;
        }

        $maxHoursAfter = max(25, (int) config('trial_subscription.followup_max_hours_after_expiry', 72));
        if ($endedAt->lt(now()->subHours($maxHoursAfter))) {
            return false;
        }

        return true;
    }

    private function eligibleTrialsEndingAfter(): ?Carbon
    {
        $raw = config('trial_subscription.followup_eligible_trials_ending_after');
        if ($raw === null || $raw === '') {
            return null;
        }

        return Carbon::parse((string) $raw);
    }

    public function sendForUser(User $user): bool
    {
        return DB::transaction(function () use ($user): bool {
            /** @var User|null $locked */
            $locked = User::query()->lockForUpdate()->find($user->id);
            if ($locked === null || ! $this->isEligible($locked)) {
                return false;
            }

            $bonusHours = max(1, (int) config('trial_subscription.followup_bonus_hours', 24));

            try {
                $result = $this->trialIssuer->issueFromAdmin($locked, $bonusHours);
            } catch (XuiPanelException $e) {
                Log::warning('trial_followup: не удалось выдать бонусный триал', [
                    'user_id' => $locked->id,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }

            $brand = (string) config('marketing.brand_name', config('app.name', 'Надежда'));
            $fromAddress = (string) (config('marketing.support_email') ?: config('mail.from.address', 'support@nadezhda.space'));
            $fromName = (string) ($brand.' · команда сервиса');
            $paymentUrl = url(route('cabinet.payment', [], false));

            Mail::to($locked->email)->send(new TrialFollowupMail(
                brand: $brand,
                supportFromAddress: $fromAddress,
                supportFromName: $fromName,
                subscriptionUrl: $result->subscriptionUrl,
                paymentUrl: $paymentUrl,
                appUrl: rtrim((string) config('app.url'), '/'),
            ));

            $locked->forceFill(['trial_followup_email_sent_at' => now()])->save();

            return true;
        });
    }
}

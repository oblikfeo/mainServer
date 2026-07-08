<?php

namespace Tests\Feature;

use App\Mail\TrialFollowupMail;
use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Models\TestKey;
use App\Models\User;
use App\Services\Subscription\CreatedSubscriptionResult;
use App\Services\Subscription\TrialSubscriptionIssuer;
use App\Services\Trial\TrialFollowupEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class TrialFollowupEmailTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    private function expiredTrial(User $user, int $hoursAgo = 30): Subscription
    {
        return Subscription::query()->create([
            'user_id' => $user->id,
            'token' => 'expired-trial-'.bin2hex(random_bytes(4)),
            'fi_sub_id' => bin2hex(random_bytes(8)),
            'nl_sub_id' => bin2hex(random_bytes(8)),
            'quota_gb' => 5,
            'expiry_ms' => (int) (now()->subHours($hoursAgo)->getTimestamp() * 1000),
            'devices' => 1,
            'is_trial' => true,
        ]);
    }

    public function test_eligible_user_after_trial_expired_for_24h(): void
    {
        $user = $this->verifiedUser();
        $this->expiredTrial($user, 30);

        $service = app(TrialFollowupEmailService::class);

        $this->assertTrue($service->isEligible($user->fresh()));
        $this->assertCount(1, $service->findEligibleUsers());
    }

    public function test_not_eligible_while_trial_still_active(): void
    {
        $user = $this->verifiedUser();

        Subscription::query()->create([
            'user_id' => $user->id,
            'token' => 'active-trial',
            'fi_sub_id' => bin2hex(random_bytes(8)),
            'nl_sub_id' => bin2hex(random_bytes(8)),
            'quota_gb' => 5,
            'expiry_ms' => (int) (now()->addHour()->getTimestamp() * 1000),
            'devices' => 1,
            'is_trial' => true,
        ]);

        $service = app(TrialFollowupEmailService::class);

        $this->assertFalse($service->isEligible($user->fresh()));
    }

    public function test_not_eligible_before_24h_after_expiry(): void
    {
        $user = $this->verifiedUser();
        $this->expiredTrial($user, 12);

        $service = app(TrialFollowupEmailService::class);

        $this->assertFalse($service->isEligible($user->fresh()));
    }

    public function test_not_eligible_if_already_sent(): void
    {
        $user = $this->verifiedUser();
        $this->expiredTrial($user, 30);
        $user->forceFill(['trial_followup_email_sent_at' => now()])->save();

        $service = app(TrialFollowupEmailService::class);

        $this->assertFalse($service->isEligible($user->fresh()));
    }

    public function test_not_eligible_if_user_paid(): void
    {
        $user = $this->verifiedUser();
        $this->expiredTrial($user, 30);

        PaymentOrder::query()->create([
            'order_id' => 'ord-1',
            'user_id' => $user->id,
            'purpose' => 'new',
            'provider' => 'wata',
            'status' => 'paid',
            'amount_rub' => 290,
            'currency' => 'RUB',
            'description' => 'test',
            'tariff_plan' => 'solo',
            'tariff_period' => '1m',
            'days' => 30,
            'devices' => 2,
            'quota_gb' => 100,
            'paid_at' => now(),
        ]);

        $service = app(TrialFollowupEmailService::class);

        $this->assertFalse($service->isEligible($user->fresh()));
    }

    public function test_legacy_test_key_alone_is_not_eligible(): void
    {
        $user = $this->verifiedUser();

        TestKey::query()->create([
            'user_id' => $user->id,
            'client_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'panel_email' => 'legacy-'.bin2hex(random_bytes(4)).'@trial.local',
            'panel_sub_id' => bin2hex(random_bytes(8)),
            'token' => bin2hex(random_bytes(16)),
            'issued_at' => now()->subDays(2),
            'expires_at' => now()->subHours(30),
            'vless_url' => 'vless://example',
        ]);

        $service = app(TrialFollowupEmailService::class);

        $this->assertFalse($service->isEligible($user->fresh()));
        $this->assertCount(0, $service->findEligibleUsers());
    }

    public function test_not_eligible_if_trial_ended_before_cutoff(): void
    {
        config([
            'trial_subscription.followup_eligible_trials_ending_after' => now()->subHours(10)->format('Y-m-d H:i:s'),
        ]);

        $user = $this->verifiedUser();
        $this->expiredTrial($user, 30);

        $service = app(TrialFollowupEmailService::class);

        $this->assertFalse($service->isEligible($user->fresh()));
    }

    public function test_not_eligible_if_trial_expired_too_long_ago(): void
    {
        config([
            'trial_subscription.followup_eligible_trials_ending_after' => '2026-01-01 00:00:00',
            'trial_subscription.followup_max_hours_after_expiry' => 72,
        ]);

        $user = $this->verifiedUser();
        $this->expiredTrial($user, 100);

        $service = app(TrialFollowupEmailService::class);

        $this->assertFalse($service->isEligible($user->fresh()));
    }

    public function test_command_sends_mail_and_marks_user(): void
    {
        Mail::fake();

        $user = $this->verifiedUser();
        $this->expiredTrial($user, 30);

        $subscription = Subscription::query()->create([
            'user_id' => $user->id,
            'token' => 'bonus-trial',
            'fi_sub_id' => bin2hex(random_bytes(8)),
            'nl_sub_id' => bin2hex(random_bytes(8)),
            'quota_gb' => 5,
            'expiry_ms' => (int) (now()->addDay()->getTimestamp() * 1000),
            'devices' => 1,
            'is_trial' => true,
        ]);

        $issuer = Mockery::mock(TrialSubscriptionIssuer::class);
        $issuer->shouldReceive('issueFromAdmin')
            ->once()
            ->with(Mockery::on(fn ($u) => $u->id === $user->id), 24)
            ->andReturn(new CreatedSubscriptionResult(
                subscription: $subscription,
                subscriptionUrl: url('/sub/bonus-trial'),
                fiVlessLine: '',
                nlVlessLine: '',
                decodeWarning: null,
            ));
        $this->instance(TrialSubscriptionIssuer::class, $issuer);

        $this->artisan('trial-followup:send')
            ->assertSuccessful();

        Mail::assertSent(TrialFollowupMail::class, fn (TrialFollowupMail $mail) => $mail->hasTo($user->email));

        $this->assertNotNull($user->fresh()->trial_followup_email_sent_at);
    }

    public function test_command_does_not_resend(): void
    {
        Mail::fake();

        $user = $this->verifiedUser();
        $this->expiredTrial($user, 30);
        $user->forceFill(['trial_followup_email_sent_at' => now()->subDay()])->save();

        $issuer = Mockery::mock(TrialSubscriptionIssuer::class);
        $issuer->shouldNotReceive('issueFromAdmin');
        $this->instance(TrialSubscriptionIssuer::class, $issuer);

        $this->artisan('trial-followup:send')
            ->assertSuccessful();

        Mail::assertNothingSent();
    }
}

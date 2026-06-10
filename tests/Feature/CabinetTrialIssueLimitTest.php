<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Services\Subscription\TrialSubscriptionIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CabinetTrialIssueLimitTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedUser(): User
    {
        return User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    private function expiredTrial(User $user): Subscription
    {
        return Subscription::query()->create([
            'user_id' => $user->id,
            'token' => 'expired-trial-token',
            'fi_sub_id' => bin2hex(random_bytes(8)),
            'nl_sub_id' => bin2hex(random_bytes(8)),
            'quota_gb' => 5,
            'expiry_ms' => (int) ((time() - 3600) * 1000),
            'devices' => 1,
            'is_trial' => true,
        ]);
    }

    public function test_user_cannot_request_second_trial_after_first_expired(): void
    {
        $user = $this->verifiedUser();
        $this->expiredTrial($user);

        $this->assertFalse($user->fresh()->canSelfIssueCabinetTrial());

        $response = $this->actingAs($user)->post(route('cabinet.test_keys.store'));

        $response->assertSessionHasErrors('test_key');
        $this->assertSame(1, Subscription::query()->where('user_id', $user->id)->where('is_trial', true)->count());
    }

    public function test_referred_user_can_use_remaining_referral_slots_after_expired_trial(): void
    {
        $referrer = User::factory()->create();
        $user = $this->verifiedUser();
        $user->forceFill([
            'referred_by' => $referrer->id,
            'referral_invitee_test_issues_remaining' => 1,
        ])->save();

        $this->expiredTrial($user);

        $this->assertTrue($user->fresh()->canSelfIssueCabinetTrial());

        $issuer = Mockery::mock(TrialSubscriptionIssuer::class);
        $issuer->shouldReceive('issueFromCabinet')->once()->with(Mockery::on(fn ($u) => $u->id === $user->id), true);
        $this->instance(TrialSubscriptionIssuer::class, $issuer);

        $response = $this->actingAs($user)->post(route('cabinet.test_keys.store'));

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status', 'test-key-issued');
        $this->assertSame(0, (int) $user->fresh()->referral_invitee_test_issues_remaining);
    }

    public function test_first_trial_still_allowed_for_new_user(): void
    {
        $user = $this->verifiedUser();

        $this->assertTrue($user->canSelfIssueCabinetTrial());

        $issuer = Mockery::mock(TrialSubscriptionIssuer::class);
        $issuer->shouldReceive('issueFromCabinet')->once()->with(Mockery::on(fn ($u) => $u->id === $user->id), false);
        $this->instance(TrialSubscriptionIssuer::class, $issuer);

        $response = $this->actingAs($user)->post(route('cabinet.test_keys.store'));

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status', 'test-key-issued');
    }
}

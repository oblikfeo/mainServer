<?php

namespace Tests\Feature\Referral;

use App\Models\ReferralGrant;
use App\Models\User;
use App\Services\Referral\ReferralRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReferralRewardFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_referral_registration_grants_marks_in_database(): void
    {
        $referrer = User::factory()->create();
        $referee = new User([
            'name' => 'inv',
            'email' => 'inv@example.com',
            'password' => Hash::make('password'),
            'referred_by' => $referrer->id,
        ]);
        $referee->save();

        app(ReferralRewardService::class)->onReferredUserRegistered($referee);

        $this->assertDatabaseHas('referral_grants', [
            'grant_key' => ReferralRewardService::grantKeyFirstRegReferrer((int) $referrer->id),
            'kind' => ReferralGrant::KIND_FIRST_REG_REFERRER_DAYS,
        ]);
        $this->assertDatabaseHas('referral_grants', [
            'grant_key' => ReferralRewardService::grantKeyFirstRegRefereeCredit((int) $referee->id),
            'kind' => ReferralGrant::KIND_FIRST_REG_REFEREE_TEST_KEYS,
        ]);

        $referee->refresh();
        $this->assertSame(
            (int) config('referral.first_registration_referee_test_key_issues', 2),
            (int) $referee->referral_invitee_test_issues_remaining
        );
    }

    public function test_second_referral_registration_also_grants_referee_test_key_slots(): void
    {
        $referrer = User::factory()->create();
        $first = User::factory()->create();
        $first->referred_by = $referrer->id;
        $first->save();

        app(ReferralRewardService::class)->onReferredUserRegistered($first);

        $second = User::factory()->create();
        $second->referred_by = $referrer->id;
        $second->save();

        app(ReferralRewardService::class)->onReferredUserRegistered($second);

        $second->refresh();
        $this->assertSame(
            (int) config('referral.first_registration_referee_test_key_issues', 2),
            (int) $second->referral_invitee_test_issues_remaining
        );
        $this->assertDatabaseHas('referral_grants', [
            'grant_key' => ReferralRewardService::grantKeyFirstRegRefereeCredit((int) $second->id),
            'kind' => ReferralGrant::KIND_FIRST_REG_REFEREE_TEST_KEYS,
        ]);
    }

    public function test_milestone_grants_one_month_and_three_months(): void
    {
        $referrer = User::factory()->create();
        $service = app(ReferralRewardService::class);

        for ($i = 0; $i < 10; $i++) {
            $referee = User::factory()->create(['referred_by' => $referrer->id]);
            $referee->subscriptions()->create([
                'token' => 'tok'.$i.str_repeat('a', 8),
                'devices' => 2,
                'quota_gb' => 100,
                'expiry_ms' => (int) (now()->addMonth()->getTimestamp() * 1000),
                'is_trial' => false,
            ]);
        }

        $service->refreshMilestoneGrants((int) $referrer->id);

        $this->assertDatabaseHas('referral_grants', [
            'grant_key' => ReferralRewardService::grantKeyMilestoneOneMonth((int) $referrer->id),
            'kind' => ReferralGrant::KIND_MILESTONE_ONE_MONTH,
        ]);
        $this->assertDatabaseHas('referral_grants', [
            'grant_key' => ReferralRewardService::grantKeyMilestoneThreeMonths((int) $referrer->id),
            'kind' => ReferralGrant::KIND_MILESTONE_THREE_MONTHS,
        ]);
        $this->assertDatabaseMissing('referral_grants', [
            'kind' => ReferralGrant::KIND_MILESTONE_UNLIMITED_TRAFFIC,
            'beneficiary_user_id' => $referrer->id,
        ]);
    }
}

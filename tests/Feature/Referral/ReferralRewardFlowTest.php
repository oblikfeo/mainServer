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
}

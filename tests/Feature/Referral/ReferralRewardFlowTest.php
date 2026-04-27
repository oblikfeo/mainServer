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
            'kind' => ReferralGrant::KIND_FIRST_REG_REFEREE_TEST_CREDIT,
        ]);

        $referee->refresh();
        $this->assertSame(
            (int) config('referral.first_registration_referee_test_hours', 8),
            (int) $referee->referral_test_credit_hours
        );
    }
}

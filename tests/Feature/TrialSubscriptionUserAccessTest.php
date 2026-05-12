<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialSubscriptionUserAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubscription(User $user, array $overrides = []): Subscription
    {
        $defaults = [
            'user_id' => $user->id,
            'token' => rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '='),
            'fi_sub_id' => bin2hex(random_bytes(8)),
            'nl_sub_id' => bin2hex(random_bytes(8)),
            'quota_gb' => 5,
            'expiry_ms' => (int) ((time() + 86400) * 1000),
            'devices' => 1,
            'is_trial' => false,
        ];

        return Subscription::query()->create(array_merge($defaults, $overrides));
    }

    public function test_hide_trial_offer_when_active_paid_subscription_exists(): void
    {
        $user = User::factory()->create();
        $this->makeSubscription($user, [
            'is_trial' => false,
            'expiry_ms' => (int) ((time() + 3600) * 1000),
        ]);

        $this->assertTrue($user->fresh()->hasActiveNonTrialSubscription());
        $this->assertTrue($user->fresh()->shouldHideTestSubscriptionOffer());
    }

    public function test_trial_subscription_does_not_hide_offer_and_counts_as_active_trial(): void
    {
        $user = User::factory()->create();
        $this->makeSubscription($user, [
            'is_trial' => true,
            'expiry_ms' => (int) ((time() + 3600) * 1000),
        ]);

        $this->assertFalse($user->fresh()->hasActiveNonTrialSubscription());
        $this->assertFalse($user->fresh()->shouldHideTestSubscriptionOffer());
        $this->assertNotNull($user->fresh()->activeTrialSubscription());
    }

    public function test_expired_paid_subscription_does_not_block_trial_offer(): void
    {
        $user = User::factory()->create();
        $this->makeSubscription($user, [
            'is_trial' => false,
            'expiry_ms' => (int) ((time() - 3600) * 1000),
        ]);

        $this->assertFalse($user->fresh()->hasActiveNonTrialSubscription());
        $this->assertFalse($user->fresh()->shouldHideTestSubscriptionOffer());
    }
}

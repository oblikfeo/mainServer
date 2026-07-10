<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Services\Subscription\ApplySubscriptionRenewalPack;
use App\Services\Subscription\SubscriptionCalendarExtension;
use App\Services\Xui\XuiSubscriptionLimitIpSync;
use App\Services\Xui\XuiSubscriptionQuotaSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ApplySubscriptionRenewalPackTest extends TestCase
{
    use RefreshDatabase;

    private function makeSubscription(User $user, array $overrides = []): Subscription
    {
        return Subscription::query()->create(array_merge([
            'user_id' => $user->id,
            'token' => 'tok-'.bin2hex(random_bytes(6)),
            'fi_sub_id' => bin2hex(random_bytes(8)),
            'nl_sub_id' => bin2hex(random_bytes(8)),
            'quota_gb' => 100,
            'expiry_ms' => (int) ((time() + 86400) * 1000),
            'devices' => 2,
            'is_trial' => false,
        ], $overrides));
    }

    /** @return array{0: ApplySubscriptionRenewalPack, 1: SubscriptionCalendarExtension&\Mockery\MockInterface} */
    private function packWithMocks(): array
    {
        $calendar = Mockery::mock(SubscriptionCalendarExtension::class);
        $calendar->shouldReceive('addCalendarDays')
            ->andReturnUsing(function (Subscription $sub, float|int $days): void {
                $addMs = (int) round(((float) $days) * 86_400_000);
                $nowMs = (int) (now()->getTimestamp() * 1000);
                $base = max((int) $sub->expiry_ms, $nowMs);
                $sub->expiry_ms = $base + $addMs;
                $sub->save();
            });

        $quota = Mockery::mock(XuiSubscriptionQuotaSync::class);
        $quota->shouldReceive('syncForSubscription')->andReturnNull();
        $limitIp = Mockery::mock(XuiSubscriptionLimitIpSync::class);
        $limitIp->shouldReceive('syncForSubscription')->andReturnNull();

        return [new ApplySubscriptionRenewalPack($calendar, $quota, $limitIp), $calendar];
    }

    public function test_trial_renewal_becomes_paid_and_gets_solo_device_floor(): void
    {
        $user = User::factory()->create();
        $trial = $this->makeSubscription($user, [
            'is_trial' => true,
            'devices' => 1,
            'quota_gb' => 5,
            'expiry_ms' => (int) ((time() + 3600) * 1000),
        ]);

        [$pack] = $this->packWithMocks();
        $result = $pack->apply($trial->id, 30, 100, 0, 'solo');

        $this->assertFalse($result->is_trial);
        $this->assertSame(2, (int) $result->devices);
        $this->assertSame(105, (int) $result->quota_gb);
        $this->assertGreaterThan((int) $trial->expiry_ms, (int) $result->expiry_ms);
    }

    public function test_trial_renewal_family_plan_gets_five_devices(): void
    {
        $user = User::factory()->create();
        $trial = $this->makeSubscription($user, [
            'is_trial' => true,
            'devices' => 1,
            'quota_gb' => 5,
        ]);

        [$pack] = $this->packWithMocks();
        $result = $pack->apply($trial->id, 30, 250, 0, 'family');

        $this->assertFalse($result->is_trial);
        $this->assertSame(5, (int) $result->devices);
    }

    public function test_paid_renewal_stays_paid_and_preserves_devices(): void
    {
        $user = User::factory()->create();
        $paid = $this->makeSubscription($user, [
            'is_trial' => false,
            'devices' => 2,
            'quota_gb' => 50,
            'expiry_ms' => (int) ((time() + 7 * 86400) * 1000),
        ]);
        $beforeExpiry = (int) $paid->expiry_ms;

        [$pack] = $this->packWithMocks();
        $result = $pack->apply($paid->id, 30, 100, 0, 'solo');

        $this->assertFalse($result->is_trial);
        $this->assertSame(2, (int) $result->devices);
        $this->assertSame(150, (int) $result->quota_gb);
        $this->assertGreaterThan($beforeExpiry, (int) $result->expiry_ms);
    }

    public function test_expired_paid_renewal_extends_from_now(): void
    {
        $user = User::factory()->create();
        $paid = $this->makeSubscription($user, [
            'is_trial' => false,
            'expiry_ms' => (int) ((time() - 86400) * 1000),
        ]);

        [$pack] = $this->packWithMocks();
        $result = $pack->apply($paid->id, 30, 0, 0, 'solo');

        $nowMs = (int) (now()->getTimestamp() * 1000);
        $this->assertGreaterThanOrEqual($nowMs + (29 * 86_400_000), (int) $result->expiry_ms);
        $this->assertFalse($result->is_trial);
    }
}

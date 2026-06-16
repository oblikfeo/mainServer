<?php

namespace Tests\Unit;

use App\Models\Subscription;
use App\Services\Payments\BonusExtraDevicePricing;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BonusExtraDevicePricingTest extends TestCase
{
    private BonusExtraDevicePricing $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricing = new BonusExtraDevicePricing;
    }

    public function test_amount_for_one_to_thirty_days_is_one_hundred(): void
    {
        $sub = $this->subscriptionExpiringInDays(1);
        $this->assertSame(100, $this->pricing->amountRubForSubscription($sub));

        $sub = $this->subscriptionExpiringInDays(30);
        $this->assertSame(100, $this->pricing->amountRubForSubscription($sub));
    }

    public function test_amount_increases_every_thirty_days(): void
    {
        $this->assertSame(200, $this->pricing->amountRubForSubscription($this->subscriptionExpiringInDays(31)));
        $this->assertSame(200, $this->pricing->amountRubForSubscription($this->subscriptionExpiringInDays(60)));
        $this->assertSame(300, $this->pricing->amountRubForSubscription($this->subscriptionExpiringInDays(61)));
        $this->assertSame(300, $this->pricing->amountRubForSubscription($this->subscriptionExpiringInDays(90)));
        $this->assertSame(400, $this->pricing->amountRubForSubscription($this->subscriptionExpiringInDays(91)));
    }

    public function test_expired_subscription_costs_zero(): void
    {
        $sub = new Subscription([
            'expiry_ms' => now()->subDay()->getTimestampMs(),
            'devices' => 2,
        ]);

        $this->assertSame(0, $this->pricing->remainingActiveDays($sub));
        $this->assertSame(0, $this->pricing->amountRubForSubscription($sub));
    }

    private function subscriptionExpiringInDays(int $days): Subscription
    {
        $exp = now()->addDays($days)->startOfDay();

        return new Subscription([
            'expiry_ms' => (int) ($exp->getTimestamp() * 1000),
            'devices' => 2,
        ]);
    }
}

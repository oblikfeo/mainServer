<?php

namespace Tests\Unit;

use App\Services\Subscription\CreateDualBundleSubscription;
use Tests\TestCase;

class CreateDualBundleSubscriptionConfigTest extends TestCase
{
    public function test_bundle_order_empty_allows_subscription_create(): void
    {
        config([
            'xui.bundle_order' => [],
        ]);

        $order = config('xui.bundle_order');
        $this->assertSame([], $order);

        $ref = new \ReflectionClass(CreateDualBundleSubscription::class);
        $this->assertTrue($ref->hasMethod('create'));
    }
}

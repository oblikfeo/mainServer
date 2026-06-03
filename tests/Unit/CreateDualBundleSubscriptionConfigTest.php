<?php

namespace Tests\Unit;

use App\Services\Subscription\CreateDualBundleSubscription;
use Tests\TestCase;

class CreateDualBundleSubscriptionConfigTest extends TestCase
{
    public function test_bundle_order_fi_only_does_not_require_nl_sub_id_in_config(): void
    {
        config([
            'xui.bundle_order' => ['fi'],
            'xui.nodes.fi' => [
                'panel_base' => 'https://example.test/panel',
                'panel_username' => 'u',
                'panel_password' => 'p',
                'inbound_id' => 1,
                'client_email_prefix' => 'fi',
            ],
        ]);

        $order = config('xui.bundle_order');
        $this->assertSame(['fi'], $order);
        $this->assertNotContains('nl', $order);

        $ref = new \ReflectionClass(CreateDualBundleSubscription::class);
        $this->assertTrue($ref->hasMethod('create'));
    }
}

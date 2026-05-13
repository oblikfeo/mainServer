<?php

namespace Tests\Unit;

use App\Services\Subscription\HappProviderIdSubscriptionExtras;
use Tests\TestCase;

final class HappProviderIdSubscriptionExtrasTest extends TestCase
{
    protected function tearDown(): void
    {
        config([
            'xui.happ_provider_id' => '',
            'xui.happ_provider_id_by_token' => [],
        ]);
        parent::tearDown();
    }

    public function test_empty_when_no_config(): void
    {
        config(['xui.happ_provider_id' => '', 'xui.happ_provider_id_by_token' => []]);

        $x = HappProviderIdSubscriptionExtras::forSubscriptionToken('any');

        $this->assertSame('', $x['body_prefix']);
        $this->assertSame([], $x['headers']);
    }

    public function test_global_id_applies_to_any_token(): void
    {
        config(['xui.happ_provider_id' => 'pid-global', 'xui.happ_provider_id_by_token' => []]);

        $x = HappProviderIdSubscriptionExtras::forSubscriptionToken('tok');

        $this->assertSame("#providerid pid-global\n", $x['body_prefix']);
        $this->assertSame(['providerid' => 'pid-global'], $x['headers']);
    }

    public function test_per_token_overrides_global(): void
    {
        config([
            'xui.happ_provider_id' => 'global',
            'xui.happ_provider_id_by_token' => ['5v6lia1K' => 'only-this'],
        ]);

        $scoped = HappProviderIdSubscriptionExtras::forSubscriptionToken('5v6lia1K');
        $this->assertSame(['providerid' => 'only-this'], $scoped['headers']);

        $other = HappProviderIdSubscriptionExtras::forSubscriptionToken('other-token');
        $this->assertSame(['providerid' => 'global'], $other['headers']);
    }

    public function test_blank_map_value_falls_back_to_global(): void
    {
        config([
            'xui.happ_provider_id' => 'global',
            'xui.happ_provider_id_by_token' => ['5v6lia1K' => '   '],
        ]);

        $x = HappProviderIdSubscriptionExtras::forSubscriptionToken('5v6lia1K');

        $this->assertSame(['providerid' => 'global'], $x['headers']);
    }
}

<?php

namespace Tests\Unit;

use App\Services\Subscription\HappRoutingMergedInput;
use App\Services\Subscription\HappRoutingSubscriptionLine;
use Tests\TestCase;

final class HappRoutingMergedInputTest extends TestCase
{
    protected function tearDown(): void
    {
        config([
            'xui.happ_routing.direct_sites' => [
                'domain:vk.com',
                'domain:yandex.ru',
                'domain:ozon.ru',
                'domain:wildberries.ru',
                'domain:push.apple.com',
            ],
            'xui.sub_extra_ruvds' => [
                'enabled' => false,
                'vless_uri' => '',
            ],
        ]);
        parent::tearDown();
    }

    public function test_keeps_full_direct_list_when_ruvds_disabled(): void
    {
        $sites = HappRoutingMergedInput::mergedDirectSites();

        $this->assertContains('domain:ozon.ru', $sites);
        $this->assertContains('domain:yandex.ru', $sites);
    }

    public function test_keeps_full_direct_list_when_ruvds_enabled(): void
    {
        config([
            'xui.sub_extra_ruvds' => [
                'enabled' => true,
                'vless_uri' => 'vless://test@1.2.3.4:443',
            ],
        ]);

        $sites = HappRoutingMergedInput::mergedDirectSites();

        $this->assertContains('domain:ozon.ru', $sites);
        $this->assertContains('domain:yandex.ru', $sites);
    }

    public function test_routing_off_in_subscription_when_ruvds_enabled(): void
    {
        config([
            'xui.happ_routing.enabled' => true,
            'xui.happ_routing.routing_off_when_ruvds' => true,
            'xui.sub_extra_ruvds' => [
                'enabled' => true,
                'vless_uri' => 'vless://test@1.2.3.4:443',
            ],
        ]);

        $this->assertSame(
            HappRoutingSubscriptionLine::ROUTING_OFF_DEEPLINK,
            HappRoutingSubscriptionLine::feedRoutingLine(),
        );
    }

    public function test_normal_routing_profile_when_ruvds_and_routing_off_disabled(): void
    {
        config([
            'xui.happ_routing.enabled' => true,
            'xui.happ_routing.routing_off_when_ruvds' => false,
            'xui.sub_extra_ruvds' => [
                'enabled' => true,
                'vless_uri' => 'vless://test@1.2.3.4:443',
            ],
        ]);

        $line = HappRoutingSubscriptionLine::feedRoutingLine();
        $this->assertNotNull($line);
        $this->assertStringStartsWith('happ://routing/', (string) $line);
        $this->assertNotSame(HappRoutingSubscriptionLine::ROUTING_OFF_DEEPLINK, $line);
    }
}

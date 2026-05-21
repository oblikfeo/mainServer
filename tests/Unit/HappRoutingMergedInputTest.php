<?php

namespace Tests\Unit;

use App\Services\Subscription\HappRoutingMergedInput;
use Tests\TestCase;

final class HappRoutingMergedInputTest extends TestCase
{
    protected function tearDown(): void
    {
        config([
            'xui.happ_routing.direct_sites' => [
                'domain:vk.com',
                'domain:ozon.ru',
                'domain:wildberries.ru',
            ],
            'xui.happ_routing.direct_sites_exclude_when_ruvds' => [
                'domain:ozon.ru',
                'domain:wildberries.ru',
            ],
            'xui.sub_extra_ruvds' => [
                'enabled' => false,
                'vless_uri' => '',
            ],
        ]);
        parent::tearDown();
    }

    public function test_keeps_shopping_in_direct_when_ruvds_disabled(): void
    {
        $sites = HappRoutingMergedInput::mergedDirectSites();

        $this->assertContains('domain:ozon.ru', $sites);
        $this->assertContains('domain:wildberries.ru', $sites);
    }

    public function test_excludes_shopping_from_direct_when_ruvds_enabled(): void
    {
        config([
            'xui.sub_extra_ruvds' => [
                'enabled' => true,
                'vless_uri' => 'vless://test@1.2.3.4:443',
            ],
        ]);

        $sites = HappRoutingMergedInput::mergedDirectSites();

        $this->assertContains('domain:vk.com', $sites);
        $this->assertNotContains('domain:ozon.ru', $sites);
        $this->assertNotContains('domain:wildberries.ru', $sites);
    }
}

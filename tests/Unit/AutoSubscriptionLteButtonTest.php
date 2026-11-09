<?php

namespace Tests\Unit;

use App\Services\Subscription\AutoSubscriptionFeedRenderer;
use Tests\TestCase;

final class AutoSubscriptionLteButtonTest extends TestCase
{
    private const WIFI = 'vless://11111111-1111-1111-1111-111111111111@10.0.0.1:2053?type=tcp&security=reality&sni=example.com&pbk=key&sid=ab#WiFi 1';

    private const LTE_1 = 'vless://22222222-2222-2222-2222-222222222222@cdn-one.example:443?type=xhttp&security=tls&sni=cdn-one.example&path=%2Fapi&mode=packet-up#LTE 1';

    private const LTE_2 = 'vless://33333333-3333-3333-3333-333333333333@cdn-two.example:443?type=xhttp&security=tls&sni=cdn-two.example&path=%2Fapi&mode=packet-up#LTE 2';

    private function setPools(array $wifi, array $lte, array $extra = []): void
    {
        config([
            'xui.sub_auto.wifi' => $wifi,
            'xui.sub_auto.lte' => $lte,
            'xui.sub_auto.extra' => $extra,
            'xui.sub_auto.lte_button' => true,
            'xui.sub_auto.lte_remarks' => '📶 LTE',
            'sub_auto_direct' => ['domain:ya.ru'],
        ]);
    }

    private function renderer(): AutoSubscriptionFeedRenderer
    {
        return app(AutoSubscriptionFeedRenderer::class);
    }

    public function test_lte_button_contains_only_lte_nodes(): void
    {
        $this->setPools([self::WIFI], [self::LTE_1, self::LTE_2]);

        $doc = $this->renderer()->buildLteOnlyConfig();

        $this->assertNotNull($doc);
        $this->assertSame('📶 LTE', $doc['remarks']);
        $tags = array_column($doc['outbounds'], 'tag');
        $this->assertSame(['proxy-lte-1', 'proxy-lte-2', 'direct', 'block'], $tags);
        $this->assertSame('cdn-one.example', $doc['outbounds'][0]['settings']['vnext'][0]['address']);
        $this->assertSame(['proxy-lte-'], $doc['observatory']['subjectSelector']);

        $balancers = $doc['routing']['balancers'];
        $this->assertCount(1, $balancers);
        $this->assertSame('leastPing', $balancers[0]['strategy']['type']);
        $this->assertSame('proxy-lte-1', $balancers[0]['fallbackTag']);

        $json = json_encode($doc, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('wifi', $json);
        $this->assertStringNotContainsString('loopback', $json);

        $rules = $doc['routing']['rules'];
        $this->assertSame('direct', $rules[1]['outboundTag']);
        $this->assertSame('lte-auto', end($rules)['balancerTag']);
    }

    public function test_main_auto_is_unchanged_by_lte_button(): void
    {
        $this->setPools([self::WIFI], [self::LTE_1, self::LTE_2]);

        $main = $this->renderer()->buildConfig();

        $this->assertSame(
            ['proxy-wifi-1', 'proxy-lte-1', 'proxy-lte-2', 'loopback-lte', 'direct', 'block'],
            array_column($main['outbounds'], 'tag'),
        );
        $this->assertSame(['wifi-pool', 'lte-auto'], array_column($main['routing']['balancers'], 'tag'));
    }

    public function test_no_lte_button_without_lte_or_wifi_pool_or_when_disabled(): void
    {
        $this->setPools([self::WIFI], []);
        $this->assertNull($this->renderer()->buildLteOnlyConfig());

        $this->setPools([], [self::LTE_1]);
        $this->assertNull($this->renderer()->buildLteOnlyConfig());

        $this->setPools([self::WIFI], [self::LTE_1]);
        config(['xui.sub_auto.lte_button' => false]);
        $this->assertNull($this->renderer()->buildLteOnlyConfig());
    }
}

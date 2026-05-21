<?php

namespace Tests\Unit;

use App\Services\Subscription\SubscriptionExtraShareLines;
use Tests\TestCase;

final class SubscriptionExtraShareLinesTest extends TestCase
{
    private const VLESS_HOME = 'vless://11111111-1111-1111-1111-111111111111@185.121.14.153:443?security=reality&encryption=none&type=tcp#home';

    private const VLESS_RUVDS = 'vless://22222222-2222-2222-2222-222222222222@195.133.198.100:443?security=reality&encryption=none&type=tcp#ruvds';

    protected function tearDown(): void
    {
        config([
            'xui.sub_extra' => [
                'enabled' => false,
                'vless_uri' => '',
                'vless_title' => '',
                'vless_subtitle' => '',
            ],
            'xui.sub_extra_ruvds' => [
                'enabled' => false,
                'vless_uri' => '',
                'vless_title' => '',
                'vless_subtitle' => '',
            ],
            'xui.vless_server_description_format' => 'b64',
        ]);
        parent::tearDown();
    }

    public function test_lines_empty_when_nothing_configured(): void
    {
        $this->assertSame([], SubscriptionExtraShareLines::lines());
    }

    public function test_lines_order_home_then_ruvds(): void
    {
        config([
            'xui.sub_extra' => [
                'enabled' => true,
                'vless_uri' => self::VLESS_HOME,
                'vless_title' => 'Wi-Fi',
                'vless_subtitle' => '',
            ],
            'xui.sub_extra_ruvds' => [
                'enabled' => true,
                'vless_uri' => self::VLESS_RUVDS,
                'vless_title' => '🇭🇰 Мобильная сеть [1]',
                'vless_subtitle' => '',
            ],
        ]);

        $lines = SubscriptionExtraShareLines::lines();

        $this->assertCount(2, $lines);
        $this->assertStringContainsString('@185.121.14.153:', $lines[0]);
        $this->assertStringContainsString('@195.133.198.100:', $lines[1]);
    }

    public function test_ordered_with_bundle_puts_panel_lines_after_shared(): void
    {
        config([
            'xui.sub_extra_ruvds' => [
                'enabled' => true,
                'vless_uri' => self::VLESS_RUVDS,
                'vless_title' => 'RUVDS',
                'vless_subtitle' => '',
            ],
        ]);

        $bundle = [
            'vless_entries' => [
                ['key' => 'fi', 'line' => 'vless://fi@1.2.3.4:443#fi'],
                ['key' => 'nl', 'line' => 'vless://nl@5.6.7.8:443#nl'],
            ],
        ];

        $lines = SubscriptionExtraShareLines::orderedWithBundle($bundle);

        $this->assertCount(3, $lines);
        $this->assertStringContainsString('@195.133.198.100:', $lines[0]);
        $this->assertSame('vless://fi@1.2.3.4:443#fi', $lines[1]);
        $this->assertSame('vless://nl@5.6.7.8:443#nl', $lines[2]);
    }
}

<?php

namespace Tests\Unit;

use App\Services\Subscription\SubscriptionExtraShareLines;
use Tests\TestCase;

final class SubscriptionExtraShareLinesTest extends TestCase
{
    private const VLESS_RUVDS = 'vless://22222222-2222-2222-2222-222222222222@195.133.198.100:443?security=reality&encryption=none&type=tcp#ruvds';

    private const VLESS_777 = 'vless://44444444-4444-4444-4444-444444444444@169.40.15.141:443?security=reality&encryption=none&type=tcp#777';

    private const VLESS_NL_SHARED = 'vless://33333333-3333-3333-3333-333333333333@158.160.136.187:443?security=reality&encryption=none&type=tcp#nl-shared';

    protected function tearDown(): void
    {
        config([
            'xui.sub_extra_ruvds' => [
                'enabled' => false,
                'vless_uri' => '',
                'vless_title' => '',
                'vless_subtitle' => '',
            ],
            'xui.sub_extra_777' => [
                'enabled' => false,
                'vless_uri' => '',
                'vless_title' => '',
                'vless_subtitle' => '',
            ],
            'xui.sub_extra_nl' => [
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

    public function test_lines_order_777_then_ruvds(): void
    {
        config([
            'xui.sub_extra_777' => [
                'enabled' => true,
                'vless_uri' => self::VLESS_777,
                'vless_title' => '🇧🇬 Быстрый Wi--Fi',
                'vless_subtitle' => '',
            ],
            'xui.sub_extra_ruvds' => [
                'enabled' => true,
                'vless_uri' => self::VLESS_RUVDS,
                'vless_title' => '🇭🇰 МегаФон, Теле2, Йота',
                'vless_subtitle' => '',
            ],
        ]);

        $lines = SubscriptionExtraShareLines::lines();

        $this->assertCount(2, $lines);
        $this->assertStringContainsString('@169.40.15.141:', $lines[0]);
        $this->assertStringContainsString('Wi--Fi', $lines[0]);
        $this->assertStringContainsString('@195.133.198.100:', $lines[1]);
    }

    public function test_ordered_with_bundle_puts_panel_fi_after_shared(): void
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
                ['key' => 'fi', 'line' => 'vless://fi@158.160.158.78:443#fi'],
            ],
        ];

        $lines = SubscriptionExtraShareLines::orderedWithBundle($bundle);

        $this->assertCount(2, $lines);
        $this->assertStringContainsString('@195.133.198.100:', $lines[0]);
        $this->assertSame('vless://fi@158.160.158.78:443#fi', $lines[1]);
    }

    public function test_ordered_with_bundle_uses_nl_shared_instead_of_panel_nl(): void
    {
        config([
            'xui.sub_extra_nl' => [
                'enabled' => true,
                'vless_uri' => self::VLESS_NL_SHARED,
                'vless_title' => '🇳🇱 Мобильная сеть [3]',
                'vless_subtitle' => 'MTC, beeline',
            ],
        ]);

        $bundle = [
            'vless_entries' => [
                ['key' => 'fi', 'line' => 'vless://fi@1.2.3.4:443#fi'],
                ['key' => 'nl', 'line' => 'vless://nl@5.6.7.8:443#nl'],
            ],
        ];

        $lines = SubscriptionExtraShareLines::orderedWithBundle($bundle);

        $this->assertCount(2, $lines);
        $this->assertSame('vless://fi@1.2.3.4:443#fi', $lines[0]);
        $this->assertStringContainsString('@158.160.136.187:', $lines[1]);
        $this->assertStringNotContainsString('@5.6.7.8:', $lines[1]);
    }

    public function test_panel_bundle_order_skips_nl_when_shared(): void
    {
        config([
            'xui.bundle_order' => ['fi', 'nl'],
            'xui.sub_extra_nl' => [
                'enabled' => true,
                'vless_uri' => self::VLESS_NL_SHARED,
                'vless_title' => 'NL',
                'vless_subtitle' => '',
            ],
        ]);

        $this->assertSame(['fi'], SubscriptionExtraShareLines::panelBundleOrder());
    }
}

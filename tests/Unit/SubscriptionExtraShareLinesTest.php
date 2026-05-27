<?php

namespace Tests\Unit;

use App\Services\Subscription\SubscriptionExtraShareLines;
use Tests\TestCase;

final class SubscriptionExtraShareLinesTest extends TestCase
{
    private const VLESS_HOME = 'vless://11111111-1111-1111-1111-111111111111@185.121.14.153:443?security=reality&encryption=none&type=tcp#home';

    private const HY2_HOME = 'hy2://user:pass@185.121.14.153:443?insecure=1#IPv4';

    private const VLESS_RUVDS = 'vless://22222222-2222-2222-2222-222222222222@195.133.198.100:443?security=reality&encryption=none&type=tcp#ruvds';

    protected function tearDown(): void
    {
        config([
            'xui.sub_extra' => [
                'enabled' => false,
                'hy2_uri' => '',
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

    public function test_lines_prefers_hy2_over_vless_for_home(): void
    {
        config([
            'xui.sub_extra' => [
                'enabled' => true,
                'hy2_uri' => self::HY2_HOME,
                'vless_uri' => self::VLESS_HOME,
                'vless_title' => '🇩🇪 Быстрый Wi-Fi',
                'vless_subtitle' => 'Hy2',
            ],
        ]);

        $lines = SubscriptionExtraShareLines::lines();

        $this->assertCount(1, $lines);
        $this->assertStringStartsWith('hy2://', $lines[0]);
        $this->assertStringContainsString('@185.121.14.153:', $lines[0]);
        $this->assertStringContainsString('🇩🇪', $lines[0]);
    }

    public function test_lines_order_home_then_ruvds(): void
    {
        config([
            'xui.sub_extra' => [
                'enabled' => true,
                'hy2_uri' => '',
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

    public function test_lines_without_vless_share_uris_keeps_hy2_only(): void
    {
        config([
            'xui.sub_extra' => [
                'enabled' => true,
                'hy2_uri' => self::HY2_HOME,
                'vless_uri' => self::VLESS_HOME,
                'vless_title' => '🇩🇪 Wi-Fi',
                'vless_subtitle' => 'Hy2',
            ],
            'xui.sub_extra_ruvds' => [
                'enabled' => true,
                'vless_uri' => self::VLESS_RUVDS,
                'vless_title' => '🇭🇰 [1]',
                'vless_subtitle' => 'Yota',
            ],
        ]);

        $lines = SubscriptionExtraShareLines::lines(false);

        $this->assertCount(1, $lines);
        $this->assertStringStartsWith('hy2://', $lines[0]);
    }

    public function test_shared_vless_profiles_for_json(): void
    {
        config([
            'xui.sub_extra_ruvds' => [
                'enabled' => true,
                'vless_uri' => self::VLESS_RUVDS,
                'vless_title' => '🇭🇰 [1]',
                'vless_subtitle' => 'Yota, Tele2',
            ],
        ]);

        $profiles = SubscriptionExtraShareLines::sharedVlessProfilesForJson();

        $this->assertCount(1, $profiles);
        $this->assertSame(self::VLESS_RUVDS, $profiles[0]['uri']);
        $this->assertSame('🇭🇰 [1]', $profiles[0]['remarks']);
        $this->assertSame('Yota, Tele2', $profiles[0]['server_description']);
    }

    public function test_ordered_with_bundle_without_panel_vless(): void
    {
        config([
            'xui.sub_extra' => [
                'enabled' => true,
                'hy2_uri' => self::HY2_HOME,
                'vless_uri' => '',
                'vless_title' => 'Wi-Fi',
                'vless_subtitle' => '',
            ],
        ]);

        $bundle = [
            'vless_entries' => [
                ['key' => 'fi', 'line' => 'vless://fi@1.2.3.4:443#fi'],
            ],
        ];

        $lines = SubscriptionExtraShareLines::orderedWithBundle($bundle, false);

        $this->assertCount(1, $lines);
        $this->assertStringStartsWith('hy2://', $lines[0]);
    }
}

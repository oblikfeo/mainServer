<?php

namespace Tests\Unit;

use App\Services\Subscription\HappSubscriptionAppManagementExtras;
use Tests\TestCase;

final class HappSubscriptionAppManagementExtrasTest extends TestCase
{
    protected function tearDown(): void
    {
        config([
            'marketing.happ_ping_result' => 'icon',
            'marketing.happ_subscription_ping_onopen_enabled' => true,
            'marketing.happ_ping_type' => '',
            'marketing.happ_check_url_via_proxy' => '',
            'marketing.subscription_announce_personalize' => false,
            'marketing.telegram_support_url' => '',
            'marketing.telegram_url' => '',
            'marketing.happ_cabinet_link_enabled' => false,
            'marketing.subscription_site_url' => '',
            'marketing.subscription_profile_web_icon_color' => '',
        ]);
        parent::tearDown();
    }

    public function test_ping_icon_mode_in_headers_and_body(): void
    {
        config([
            'marketing.happ_ping_result' => 'icon',
            'marketing.happ_subscription_ping_onopen_enabled' => true,
        ]);

        $extras = HappSubscriptionAppManagementExtras::forResponses(null);

        $this->assertSame('icon', $extras['headers']['ping-result'] ?? null);
        $this->assertSame('1', $extras['headers']['subscription-ping-onopen-enabled'] ?? null);
        $this->assertStringContainsString('#ping-result: icon', $extras['body_meta_suffix']);
        $this->assertStringContainsString('#subscription-ping-onopen-enabled: 1', $extras['body_meta_suffix']);
    }

    public function test_ping_disabled_when_result_empty(): void
    {
        config([
            'marketing.happ_ping_result' => '',
            'marketing.happ_subscription_ping_onopen_enabled' => false,
        ]);

        $extras = HappSubscriptionAppManagementExtras::forResponses(null);

        $this->assertArrayNotHasKey('ping-result', $extras['headers']);
        $this->assertStringNotContainsString('ping-result', $extras['body_meta_suffix']);
    }
}

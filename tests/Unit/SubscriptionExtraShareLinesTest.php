<?php

namespace Tests\Unit;

use App\Services\Subscription\SubscriptionExtraShareLines;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SubscriptionExtraShareLinesTest extends TestCase
{
    public function test_ensure_hy2_username_fills_empty_user(): void
    {
        Config::set('xui.sub_extra.hy2_auth_user', 'nadezhda');

        $fixed = SubscriptionExtraShareLines::ensureHy2Username(
            'hysteria2://:secret-pass@example.com:443?sni=example.com#Title'
        );

        $this->assertStringStartsWith('hy2://nadezhda:secret-pass@example.com:443', $fixed);
    }

    public function test_ensure_hy2_username_keeps_existing_user(): void
    {
        $uri = 'hy2://alice:bob@example.com:443';

        $this->assertSame($uri, SubscriptionExtraShareLines::ensureHy2Username($uri));
    }

    public function test_is_configured_when_uri_present_even_if_enabled_false(): void
    {
        $this->assertTrue(SubscriptionExtraShareLines::isConfigured([
            'enabled' => false,
            'vless_uri' => 'vless://x@1.2.3.4:443',
            'hy2_uri' => '',
        ]));
    }
}

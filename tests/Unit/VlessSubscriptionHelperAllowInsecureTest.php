<?php

namespace Tests\Unit;

use App\Services\Subscription\VlessSubscriptionHelper;
use App\Services\Subscription\VlessUriToXrayOutbound;
use Tests\TestCase;

final class VlessSubscriptionHelperAllowInsecureTest extends TestCase
{
    public function test_strip_deprecated_allow_insecure_from_vless_query(): void
    {
        $in = 'vless://uuid@1.2.3.4:443?security=tls&encryption=none&type=tcp&allowInsecure=1&sni=example.com#Title';
        $out = VlessSubscriptionHelper::stripDeprecatedAllowInsecure($in);

        $this->assertStringNotContainsString('allowInsecure', $out);
        $this->assertStringContainsString('security=tls', $out);
        $this->assertStringContainsString('sni=example.com', $out);
        $this->assertStringEndsWith('#Title', $out);
    }

    public function test_normalize_panel_tls_fills_empty_sni_without_allow_insecure(): void
    {
        $in = 'vless://uuid@127.0.0.1:443?security=tls&encryption=none&type=tcp&allowInsecure=1&sni=#node';
        $out = VlessSubscriptionHelper::normalizePanelTlsVlessUri($in, 'fi.example.com');

        $this->assertStringNotContainsString('allowInsecure', $out);
        $this->assertStringContainsString('sni=fi.example.com', $out);
    }

    public function test_set_share_fragment_strips_allow_insecure_for_vless(): void
    {
        $in = 'vless://uuid@1.2.3.4:443?security=reality&allowInsecure=1#old';
        $out = VlessSubscriptionHelper::setShareFragment($in, '🇫🇮 Test', 'subtitle', 'b64');

        $this->assertStringNotContainsString('allowInsecure', $out);
    }

    public function test_xray_json_uses_pin_sha256_instead_of_allow_insecure(): void
    {
        $uri = 'vless://11111111-1111-1111-1111-111111111111@1.2.3.4:443?'
            .'security=tls&encryption=none&type=tcp&sni=example.com'
            .'&allowInsecure=1&pinSHA256=AA%3ABB%3ACC';

        $out = VlessUriToXrayOutbound::convert($uri, 'test');
        $this->assertIsArray($out);

        $tls = $out['streamSettings']['tlsSettings'] ?? [];
        $this->assertSame('AA:BB:CC', $tls['pinnedPeerCertSha256'] ?? null);
        $this->assertArrayNotHasKey('allowInsecure', $tls);
    }
}

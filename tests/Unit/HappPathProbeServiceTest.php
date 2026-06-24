<?php

namespace Tests\Unit;

use App\Services\HappPathProbeService;
use App\Services\Subscription\VlessUriToXrayOutbound;
use Tests\TestCase;

final class HappPathProbeServiceTest extends TestCase
{
    public function test_build_client_config_from_shared_vless_uri(): void
    {
        config([
            'xui.sub_extra_bg31' => [
                'enabled' => true,
                'vless_uri' => 'vless://11111111-1111-1111-1111-111111111111@31.22.10.250:443?'
                    .'type=tcp&security=reality&encryption=none&flow=xtls-rprx-vision'
                    .'&sni=www.microsoft.com&fp=chrome&pbk=abc&sid=deadbeef#Title',
                'vless_title' => '🇩🇪 Test',
            ],
            'path_probe.nodes' => [
                ['id' => 'bg31', 'extra_key' => 'sub_extra_bg31', 'title_key' => 'vless_title'],
            ],
        ]);

        $service = app(HappPathProbeService::class);
        $targets = $service->configuredTargets();

        $this->assertCount(1, $targets);
        $this->assertSame('bg31', $targets[0]['id']);
        $this->assertSame('🇩🇪 Test', $targets[0]['title']);

        $config = $service->buildClientConfig($targets[0], 10820);
        $this->assertIsArray($config);
        $this->assertSame(10820, $config['inbounds'][0]['port'] ?? null);
        $this->assertSame('vless', $config['outbounds'][0]['protocol'] ?? null);
        $this->assertSame('proxy', $config['outbounds'][0]['tag'] ?? null);
    }

    public function test_xhttp_outbound_includes_extra_and_alpn(): void
    {
        $uri = 'vless://22222222-2222-2222-2222-222222222222@cdn.nadezhda.space:443?'
            .'encryption=none&security=tls&sni=cdn.nadezhda.space&fp=chrome&type=xhttp'
            .'&path=%2Fapi%2Fv1%2Fupload%2F&host=cdn.nadezhda.space&mode=packet-up'
            .'&extra='.rawurlencode('{"path":"/api/v1/upload/","seqKey":"chunk_id"}');

        $out = VlessUriToXrayOutbound::convert($uri, 'proxy');
        $this->assertIsArray($out);

        $stream = $out['streamSettings'] ?? [];
        $this->assertSame('xhttp', $stream['network'] ?? null);
        $this->assertSame('/api/v1/upload/', $stream['xhttpSettings']['path'] ?? null);
        $this->assertSame('packet-up', $stream['xhttpSettings']['mode'] ?? null);
        $this->assertSame('chunk_id', $stream['xhttpSettings']['extra']['seqKey'] ?? null);
        $this->assertSame(['h2', 'http/1.1'], $stream['tlsSettings']['alpn'] ?? null);
    }
}

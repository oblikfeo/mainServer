<?php

namespace Tests\Unit;

use App\Services\Subscription\HappRoutingSubscriptionLine;
use PHPUnit\Framework\TestCase;

final class HappRoutingSubscriptionLineTest extends TestCase
{
    public function test_routing_off_deeplink_constant(): void
    {
        $this->assertSame('happ://routing/off', HappRoutingSubscriptionLine::ROUTING_OFF_DEEPLINK);
    }

    public function test_legacy_call_without_geo_urls_strips_geosite_and_geoip(): void
    {
        $line = HappRoutingSubscriptionLine::buildOnAddLine(
            profileName: 'direct',
            directSites: ['geosite:category-ru', 'domain:vk.com'],
            useOnAdd: true,
            extraDirectIp: ['geoip:ru', '192.168.3.0/24'],
        );

        $this->assertNotNull($line);
        $json = $this->decodeRoutingLine((string) $line);

        $this->assertSame('', $json['Geoipurl']);
        $this->assertSame('', $json['Geositeurl']);
        $this->assertContains('domain:vk.com', $json['DirectSites']);
        $this->assertNotContains('geosite:category-ru', $json['DirectSites']);
        $this->assertContains('192.168.3.0/24', $json['DirectIp']);
        $this->assertNotContains('geoip:ru', $json['DirectIp']);
    }

    public function test_only_geosite_without_url_yields_null_when_no_extra_ip(): void
    {
        $line = HappRoutingSubscriptionLine::buildOnAddLine(
            profileName: 'direct',
            directSites: ['geosite:category-ru'],
            useOnAdd: true,
            extraDirectIp: [],
        );
        $this->assertNull($line);
    }

    public function test_geo_urls_are_forwarded_and_geosite_geoip_kept(): void
    {
        $line = HappRoutingSubscriptionLine::buildOnAddLine(
            profileName: 'direct',
            directSites: ['geosite:category-ru', 'domain:push.apple.com'],
            useOnAdd: true,
            extraDirectIp: ['geoip:ru'],
            geoipUrl: 'http://195.133.198.100/geo/geoip.dat',
            geositeUrl: 'http://195.133.198.100/geo/geosite.dat',
        );

        $this->assertNotNull($line);
        $json = $this->decodeRoutingLine((string) $line);

        $this->assertSame('http://195.133.198.100/geo/geoip.dat', $json['Geoipurl']);
        $this->assertSame('http://195.133.198.100/geo/geosite.dat', $json['Geositeurl']);
        $this->assertContains('geosite:category-ru', $json['DirectSites']);
        $this->assertContains('domain:push.apple.com', $json['DirectSites']);
        $this->assertContains('geoip:ru', $json['DirectIp']);
    }

    public function test_block_sites_kept_when_geosite_url_set(): void
    {
        $line = HappRoutingSubscriptionLine::buildOnAddLine(
            profileName: 'direct',
            directSites: ['domain:vk.com'],
            useOnAdd: true,
            extraDirectIp: [],
            geoipUrl: 'https://example.org/geoip.dat',
            geositeUrl: 'https://example.org/geosite.dat',
            blockSites: ['geosite:category-ads-all', 'domain:doubleclick.net'],
            blockIp: ['geoip:cn'],
        );

        $this->assertNotNull($line);
        $json = $this->decodeRoutingLine((string) $line);

        $this->assertContains('geosite:category-ads-all', $json['BlockSites']);
        $this->assertContains('domain:doubleclick.net', $json['BlockSites']);
        $this->assertContains('geoip:cn', $json['BlockIp']);
    }

    public function test_block_only_geosite_returns_non_null_with_url(): void
    {
        $line = HappRoutingSubscriptionLine::buildOnAddLine(
            profileName: 'direct',
            directSites: [],
            useOnAdd: true,
            extraDirectIp: [],
            geositeUrl: 'https://example.org/geosite.dat',
            blockSites: ['geosite:category-ads-all'],
        );

        $this->assertNotNull($line);
    }

    public function test_doh_bootstrap_added_to_direct_ip(): void
    {
        $line = HappRoutingSubscriptionLine::buildOnAddLine(
            profileName: 'direct',
            directSites: ['domain:vk.com'],
            useOnAdd: true,
        );

        $json = $this->decodeRoutingLine((string) $line);

        $this->assertContains('77.88.8.8/32', $json['DirectIp']);
        $this->assertContains('77.88.8.1/32', $json['DirectIp']);
        $this->assertContains('192.168.0.0/16', $json['DirectIp']);
    }

    public function test_uses_yandex_doh_for_remote_and_domestic(): void
    {
        $line = HappRoutingSubscriptionLine::buildOnAddLine(
            profileName: 'direct',
            directSites: ['domain:vk.com'],
            useOnAdd: true,
        );

        $json = $this->decodeRoutingLine((string) $line);

        $this->assertSame('https://common.dot.dns.yandex.net/dns-query', $json['RemoteDNSDomain']);
        $this->assertSame('https://common.dot.dns.yandex.net/dns-query', $json['DomesticDNSDomain']);
        $this->assertSame('77.88.8.8', $json['RemoteDNSIP']);
        $this->assertSame('77.88.8.8', $json['DomesticDNSIP']);
        $this->assertSame(['common.dot.dns.yandex.net' => '77.88.8.8'], $json['DnsHosts']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeRoutingLine(string $line): array
    {
        $b64 = (string) preg_replace('#^happ://routing/(onadd|add)/#', '', $line);
        $json = json_decode((string) base64_decode($b64, true), true);
        $this->assertIsArray($json);

        return $json;
    }
}

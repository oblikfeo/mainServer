<?php

namespace Tests\Unit;

use App\Services\Subscription\HappRoutingSubscriptionLine;
use PHPUnit\Framework\TestCase;

final class HappRoutingSubscriptionLineTest extends TestCase
{
    public function test_geosite_is_stripped_and_no_geo_urls_in_line(): void
    {
        $line = HappRoutingSubscriptionLine::buildOnAddLine('direct', [
            'geosite:category-ru',
            'domain:vk.com',
        ], true, ['geoip:ru', '192.168.3.0/24']);

        $this->assertNotNull($line);
        $this->assertStringNotContainsString('Geoipurl', $line);
        $this->assertStringNotContainsString('Geositeurl', $line);
        $b64 = (string) preg_replace('#^happ://routing/(onadd|add)/#', '', (string) $line);
        $json = json_decode((string) base64_decode($b64, true), true);
        $this->assertIsArray($json);
        $this->assertContains('domain:vk.com', $json['DirectSites'] ?? []);
    }

    public function test_only_geosite_yields_null_when_no_extra_ip(): void
    {
        $line = HappRoutingSubscriptionLine::buildOnAddLine('direct', ['geosite:category-ru'], true, []);
        $this->assertNull($line);
    }
}

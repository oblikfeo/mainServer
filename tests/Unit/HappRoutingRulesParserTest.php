<?php

namespace Tests\Unit;

use App\Services\Subscription\HappRoutingRulesParser;
use PHPUnit\Framework\TestCase;

final class HappRoutingRulesParserTest extends TestCase
{
    public function test_parses_url_host_and_plain_domain(): void
    {
        $raw = "https://2ip.ru/foo\nsberbank.ru\n# skip\n";
        $out = HappRoutingRulesParser::parse($raw);

        $this->assertContains('domain:2ip.ru', $out['sites']);
        $this->assertContains('domain:sberbank.ru', $out['sites']);
        $this->assertSame([], $out['ips']);
    }

    public function test_passthrough_prefixed_and_ipv4(): void
    {
        $raw = "full:www.example.com\ngeoip:ru\n10.11.12.13\n192.168.0.0/24\n";
        $out = HappRoutingRulesParser::parse($raw);

        $this->assertContains('full:www.example.com', $out['sites']);
        $this->assertContains('geoip:ru', $out['ips']);
        $this->assertContains('10.11.12.13', $out['ips']);
        $this->assertContains('192.168.0.0/24', $out['ips']);
    }
}

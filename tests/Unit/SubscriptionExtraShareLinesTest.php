<?php

namespace Tests\Unit;

use App\Services\Subscription\SubscriptionExtraShareLines;
use Tests\TestCase;

class SubscriptionExtraShareLinesTest extends TestCase
{
    public function test_is_configured_when_vless_uri_present_even_if_enabled_false(): void
    {
        $this->assertTrue(SubscriptionExtraShareLines::isConfigured([
            'enabled' => false,
            'vless_uri' => 'vless://x@1.2.3.4:443',
        ]));
    }

    public function test_ordered_with_bundle_skips_empty_vless_entries(): void
    {
        $lines = SubscriptionExtraShareLines::orderedWithBundle([
            'vless_entries' => [
                ['line' => ''],
                ['line' => 'vless://u@1.2.3.4:443#x'],
            ],
        ]);

        $this->assertSame(['vless://u@1.2.3.4:443#x'], $lines);
    }
}

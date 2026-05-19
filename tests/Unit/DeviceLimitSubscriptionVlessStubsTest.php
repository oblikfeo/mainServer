<?php

namespace Tests\Unit;

use App\Services\Subscription\DeviceLimitSubscriptionVlessStubs;
use Tests\TestCase;

final class DeviceLimitSubscriptionVlessStubsTest extends TestCase
{
    public function test_lines_are_vless_urls(): void
    {
        $lines = DeviceLimitSubscriptionVlessStubs::lines();

        $this->assertCount(2, $lines);
        foreach ($lines as $line) {
            $this->assertStringStartsWith('vless://', $line);
        }
    }
}

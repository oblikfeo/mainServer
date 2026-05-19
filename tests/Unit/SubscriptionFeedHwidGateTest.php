<?php

namespace Tests\Unit;

use App\Services\Subscription\SubscriptionFeedHwidGate;
use Illuminate\Http\Request;
use Tests\TestCase;

final class SubscriptionFeedHwidGateTest extends TestCase
{
    public function test_does_not_persist_for_hub_ip_even_with_happ_ua(): void
    {
        config(['xui.feed_hwid_ignore_ips' => ['158.160.252.139']]);

        $request = Request::create('/sub/token', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Happ/4.9.0/ios/123',
            'HTTP_X_HWID' => 'fake-hwid',
            'REMOTE_ADDR' => '158.160.252.139',
        ]);

        $this->assertFalse(SubscriptionFeedHwidGate::shouldPersistHwidBinding($request));
    }

    public function test_persists_for_real_client_ip(): void
    {
        config(['xui.feed_hwid_ignore_ips' => ['158.160.252.139']]);

        $request = Request::create('/sub/token', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'Happ/4.9.0/ios/123',
            'HTTP_X_HWID' => 'real-hwid',
            'REMOTE_ADDR' => '188.232.220.43',
        ]);

        $this->assertTrue(SubscriptionFeedHwidGate::shouldPersistHwidBinding($request));
    }

    public function test_does_not_persist_without_happ_user_agent(): void
    {
        $request = Request::create('/sub/token', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => 'curl/8.5.0',
            'HTTP_X_HWID' => 'fake-hwid',
            'REMOTE_ADDR' => '188.232.220.43',
        ]);

        $this->assertFalse(SubscriptionFeedHwidGate::shouldPersistHwidBinding($request));
    }
}

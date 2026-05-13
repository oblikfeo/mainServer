<?php

namespace Tests\Unit;

use App\Services\Subscription\HappServerDescriptionLimiter;
use Tests\TestCase;

final class HappServerDescriptionLimiterTest extends TestCase
{
    protected function tearDown(): void
    {
        config(['xui.happ_server_description_max_chars' => 30]);
        parent::tearDown();
    }

    public function test_default_clamps_to_thirty_graphemes(): void
    {
        config(['xui.happ_server_description_max_chars' => 30]);
        $long = str_repeat('я', 45);

        $this->assertSame(str_repeat('я', 30), HappServerDescriptionLimiter::clamp($long));
    }

    public function test_respects_config_cap(): void
    {
        config(['xui.happ_server_description_max_chars' => 5]);

        $this->assertSame('12345', HappServerDescriptionLimiter::clamp('123456789'));
    }
}

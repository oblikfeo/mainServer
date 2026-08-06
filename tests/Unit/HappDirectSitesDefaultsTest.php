<?php

namespace Tests\Unit;

use Tests\TestCase;

final class HappDirectSitesDefaultsTest extends TestCase
{
    public function test_default_direct_sites_include_major_banks_and_gosuslugi(): void
    {
        /** @var list<string> $sites */
        $sites = include config_path('happ_direct_sites.php');

        foreach ([
            'domain:sberbank.ru',
            'domain:vtb.ru',
            'domain:tbank.ru',
            'domain:gosuslugi.ru',
        ] as $expected) {
            $this->assertContains($expected, $sites, 'Missing '.$expected.' in happ_direct_sites.php');
        }
    }

    public function test_xui_config_resolves_banks_when_env_not_set(): void
    {
        putenv('HAPP_DIRECT_SITES');
        unset($_ENV['HAPP_DIRECT_SITES'], $_SERVER['HAPP_DIRECT_SITES']);

        $this->app->forgetInstance('config');
        config(['xui.happ_routing.direct_sites' => null]);

        $sites = config('xui.happ_routing.direct_sites');

        $this->assertContains('domain:sberbank.ru', $sites);
    }
}

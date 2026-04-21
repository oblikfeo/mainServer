<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (['wifi', 'wifi2'] as $bundleKey) {
    $node = config('xui.nodes.'.$bundleKey, []);
    $base = rtrim((string) ($node['panel_base'] ?? ''), '/').'/';
    $user = (string) ($node['panel_username'] ?? '');
    $pass = (string) ($node['panel_password'] ?? '');
    echo "=== {$bundleKey} ===\n";
    echo "base={$base}\n";
    echo "user={$user}\n";

    $jar = new \GuzzleHttp\Cookie\CookieJar;
    $http = new \GuzzleHttp\Client([
        'base_uri' => $base,
        'verify' => false,
        'timeout' => 30,
        'cookies' => $jar,
    ]);

    try {
        $loginResp = $http->post('login', [
            'form_params' => [
                'username' => $user,
                'password' => $pass,
            ],
        ]);
        $loginBody = (string) $loginResp->getBody();
        echo "login_response={$loginBody}\n";
    } catch (\Throwable $e) {
        echo 'login_err='.$e->getMessage()."\n\n";
        continue;
    }

    try {
        $listResp = $http->get('panel/api/inbounds/list');
        $listBody = (string) $listResp->getBody();
        echo "list_response={$listBody}\n\n";
    } catch (\Throwable $e) {
        echo 'list_err='.$e->getMessage()."\n\n";
    }
}

<?php
$envFile = '/var/www/vpn-hub/.env';
$env = is_file($envFile) ? file_get_contents($envFile) : '';

$updates = [
    'SUB_NL_SHARED_ENABLED' => 'true',
    'SUB_NL_SHARED_VLESS_URI' => 'vless://3a18def7-5d31-4a3c-b7d4-498e85173bfb@158.160.136.187:443?encryption=none&flow=xtls-rprx-vision&fp=chrome&pbk=MKst4sKpktShAm-jztsuSFfeHWIctBtrFOUJMXieSh8&security=reality&sid=7958849c&sni=www.icloud.com&type=tcp',
    'SUB_NL_SHARED_VLESS_TITLE' => '"🇷🇺 Тестирование"',
    'SUB_NL_SHARED_VLESS_SUBTITLE' => '',
];

foreach ($updates as $key => $value) {
    $pattern = '/^'.preg_quote($key, '/').'=.*$/m';
    $line = $key.'='.$value;
    if (preg_match($pattern, $env)) {
        $env = preg_replace($pattern, $line, $env);
    } else {
        $env = rtrim($env)."\n".$line."\n";
    }
}

file_put_contents($envFile, $env);
echo "ENV_PATCH_OK\n";

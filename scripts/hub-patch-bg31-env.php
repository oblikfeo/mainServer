<?php
$envFile = '/var/www/vpn-hub/.env';
$env = is_file($envFile) ? file_get_contents($envFile) : '';

$updates = [
    'SUB_BG31_ENABLED' => 'true',
    'SUB_BG31_VLESS_URI' => 'vless://41db8d58-42a7-416d-958d-8c3f62552a50@31.22.10.250:443?type=tcp&security=reality&sni=www.microsoft.com&fp=chrome&pbk=fOVyu3dutROsm9P9mrmE_ORI8ZmTXMRM8iQ_wun3zHA&sid=a1b2c3d4&spx=%2F&flow=xtls-rprx-vision',
    'SUB_BG31_VLESS_TITLE' => '"🇩🇪 Быстрый Wi-Fi"',
    'SUB_BG31_VLESS_SUBTITLE' => '',
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
echo "ENV_PATCH_BG31_OK\n";

<?php
$envFile = '/var/www/vpn-hub/.env';
$env = is_file($envFile) ? file_get_contents($envFile) : '';

$updates = [
    'SUB_777_ENABLED' => 'true',
    'SUB_777_VLESS_URI' => 'vless://8514d862-4b38-4c67-9d81-036919822285@169.40.15.141:443?type=tcp&security=reality&sni=www.microsoft.com&fp=chrome&pbk=gmRh1p7ByPBKYm4baCj9Oh7vTKbmbbssuJ7LCGHQTVg&sid=a1b2c3d4&spx=%2F&flow=xtls-rprx-vision',
    'SUB_777_VLESS_TITLE' => '"🇧🇬 Быстрый Wi--Fi"',
    'SUB_777_VLESS_SUBTITLE' => '',
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
echo "ENV_PATCH_777_OK\n";

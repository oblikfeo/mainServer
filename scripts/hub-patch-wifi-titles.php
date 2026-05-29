<?php
$envFile = '/var/www/vpn-hub/.env';
$env = is_file($envFile) ? file_get_contents($envFile) : '';

$updates = [
    'SUB_EXTRA_VLESS_TITLE' => '"🇩🇪 Быстрый Wi-Fi"',
    'SUB_777_VLESS_TITLE' => '"🇧🇬 Быстрый Wi--Fi"',
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
echo "ENV_PATCH_WIFI_TITLES_OK\n";

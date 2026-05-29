<?php
$envFile = '/var/www/vpn-hub/.env';
$env = is_file($envFile) ? file_get_contents($envFile) : '';

$key = 'SUB_EXTRA_VLESS_TITLE';
$value = '"🇩🇪 Быстрый Wi-Fi"';
$pattern = '/^'.preg_quote($key, '/').'=.*$/m';
$line = $key.'='.$value;
if (preg_match($pattern, $env)) {
    $env = preg_replace($pattern, $line, $env);
} else {
    $env = rtrim($env)."\n".$line."\n";
}
file_put_contents($envFile, $env);
echo "ENV_PATCH_LITNETS_TITLE_OK\n";

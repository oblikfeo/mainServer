<?php
/**
 * Восстановление после смены IP Yandex (2026-06): hub .env + 3 узла Happ (777, RUVDS, FI).
 * Запуск на hub: php /var/www/vpn-hub/scripts/hub-restore-yandex-2026.php
 */
$envFile = '/var/www/vpn-hub/.env';
if (! is_file($envFile)) {
    fwrite(STDERR, "NO_ENV\n");
    exit(1);
}

$replacements = [
    '158.160.252.139' => '158.160.200.205',
    '158.160.241.36' => '158.160.158.78',
    '158.160.136.187' => '158.160.164.110',
];

$updates = [
    'SUB_EXTRA_ENABLED' => 'false',
    'SUB_EXTRA_HY2_URI' => '',
    'SUB_EXTRA_VLESS_URI' => '',
    'SUB_NL_SHARED_ENABLED' => 'false',
    'SUB_NL_SHARED_VLESS_URI' => '',
    'SUB_777_ENABLED' => 'true',
    'SUB_RUVDS_ENABLED' => 'true',
    'TEST_KEYS_PANEL_BASE' => '',
    'TEST_KEYS_SUB_ORIGIN' => '',
    'TEST_KEYS_PUB_HOST' => '',
    'LINK_TRIAL_IP' => '',
    'SUB_FEED_HWID_IGNORE_IPS' => '127.0.0.1,::1,158.160.200.205,195.133.198.100,158.160.158.78,169.40.15.141',
];

$env = file_get_contents($envFile);
foreach ($replacements as $from => $to) {
    $env = str_replace($from, $to, $env);
}

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
echo "ENV_RESTORE_YANDEX_2026_OK\n";

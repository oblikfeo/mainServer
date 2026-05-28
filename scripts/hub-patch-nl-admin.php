<?php
$envFile = '/var/www/vpn-hub/.env';
$env = is_file($envFile) ? file_get_contents($envFile) : '';

$updates = [
    'LINK_NL_IP' => '158.160.136.187',
    'LINK_NL_SSH_USER' => 'ubuntu',
    'LINK_NL_SSH_KEY' => '/var/www/vpn-hub/storage/app/ssh/yandex11_ed25519',
    'LINK_NL_CLIENT_TCP_PORT' => '443',
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
echo "ENV_LINK_NL_OK\n";

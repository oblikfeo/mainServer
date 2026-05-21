<?php
$envFile = '/var/www/vpn-hub/.env';
$env = is_file($envFile) ? file_get_contents($envFile) : '';

$updates = [
    'LINK_RUVDS_IP' => '195.133.198.100',
    'LINK_RUVDS_SSH_USER' => 'root',
    'LINK_RUVDS_SSH_KEY' => '/var/www/vpn-hub/storage/app/ssh/ruvds_ed25519',
    'LINK_RUVDS_CLIENT_TCP_PORT' => '443',
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
echo "ENV_LINK_RUVDS_OK\n";

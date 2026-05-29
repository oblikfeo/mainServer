<?php
$envFile = '/var/www/vpn-hub/.env';
$env = is_file($envFile) ? file_get_contents($envFile) : '';

$updates = [
    'LINK_777_IP' => '169.40.15.141',
    'LINK_777_SSH_USER' => 'root',
    'LINK_777_SSH_KEY' => '/var/www/vpn-hub/storage/app/ssh/777_ed25519',
    'LINK_777_CLIENT_TCP_PORT' => '443',
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
echo "ENV_LINK_777_OK\n";

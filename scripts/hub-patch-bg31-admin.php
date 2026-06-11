<?php
$envFile = '/var/www/vpn-hub/.env';
$env = is_file($envFile) ? file_get_contents($envFile) : '';

$updates = [
    'LINK_BG31_IP' => '31.22.10.250',
    'LINK_BG31_SSH_USER' => 'root',
    'LINK_BG31_SSH_KEY' => '/var/www/vpn-hub/storage/app/ssh/bg31_ed25519',
    'LINK_BG31_CLIENT_TCP_PORT' => '443',
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
echo "ENV_LINK_BG31_OK\n";

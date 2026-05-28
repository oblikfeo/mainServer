<?php
$envFile = '/var/www/vpn-hub/.env';
$env = is_file($envFile) ? file_get_contents($envFile) : '';

$updates = [
    'SUB_RUVDS_ENABLED' => 'true',
    'SUB_RUVDS_VLESS_URI' => 'vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@195.133.198.100:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision',
    'SUB_RUVDS_VLESS_TITLE' => '"🇭🇰 Megafon, Tele2, Yota"',
    'SUB_RUVDS_VLESS_SUBTITLE' => '',
    'XUI_FI_VLESS_NAME' => '"🇫🇮 Beeline, MTC"',
    'XUI_FI_SERVER_DESC' => '',
    'XUI_NL_VLESS_NAME' => '"🇳🇱 Мобильная сеть [3]"',
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

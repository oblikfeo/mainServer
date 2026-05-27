<?php
/** Переключить Happ routing на geo с зеркала RUVDS (без длинного domain: DirectSites). */
$envFile = '/var/www/vpn-hub/.env';
$env = is_file($envFile) ? file_get_contents($envFile) : '';

$updates = [
    'HAPP_ROUTING_ENABLED' => 'true',
    'HAPP_ROUTING_OFF_WHEN_RUVDS' => 'false',
    'HAPP_GEOIP_URL' => 'http://195.133.198.100/geo/geoip.dat',
    'HAPP_GEOSITE_URL' => 'http://195.133.198.100/geo/geosite.dat',
    'HAPP_DIRECT_SITES' => 'geosite:category-ru,domain:mtalk.google.com,domain:push.apple.com,domain:api.push.apple.com,domain:push-apple.com.akadns.net,domain:courier.push.apple.com',
    'HAPP_DIRECT_IP' => 'geoip:ru',
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
echo "patched\n";
foreach ($updates as $k => $v) {
    echo $k.'='.$v."\n";
}

<?php
/**
 * Litnets Hy2 в подписке Happ (🇩🇪 Быстрый Wi-Fi): SUB_EXTRA_HY2_URI, снять VLESS.
 * Запуск на hub: php /var/www/vpn-hub/scripts/hub-set-sub-extra-hy2.php
 */
$envFile = '/var/www/vpn-hub/.env';
$hy2Uri = 'hy2://oblik:idt6YbOM7LvxlREvfAGsL5UHm6q5va5L@185.121.14.153:443?insecure=1&obfs=salamander&obfs-password=fVuu0RQ3rzLvBEsRYGctiaLzwLItNlA&pinSHA256=6C:6D:F7:47:68:0C:F7:15:30:2D:25:72:61:B1:E4:A5:37:4A:0D:DB:41:9E:1F:64:59:DF:72:A6:7D:69:AC:C5#IPv4';

if (! is_file($envFile)) {
    fwrite(STDERR, "missing .env\n");
    exit(1);
}

$drop = ['SUB_EXTRA_HY2_URI', 'SUB_EXTRA_VLESS_URI', 'SUB_EXTRA_ENABLED'];
$set = [
    'SUB_EXTRA_ENABLED' => 'true',
    'SUB_EXTRA_HY2_URI' => $hy2Uri,
    'SUB_EXTRA_VLESS_URI' => '',
];

$lines = preg_split("/\r?\n/", (string) file_get_contents($envFile));
$out = [];
foreach ($lines as $line) {
    $trim = rtrim($line, "\r");
    if (preg_match('/^([A-Z0-9_]+)=/', $trim, $m) && in_array($m[1], $drop, true)) {
        continue;
    }
    $out[] = $trim;
}
foreach ($set as $k => $v) {
    $out[] = $v === '' ? $k.'=' : $k.'='.$v;
}
file_put_contents($envFile, implode("\n", $out)."\n");
echo "SUB_EXTRA_HY2_OK\n";
echo substr($hy2Uri, 0, 72)."…\n";

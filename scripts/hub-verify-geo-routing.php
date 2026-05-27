<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$line = (string) App\Services\Subscription\HappRoutingSubscriptionLine::feedRoutingLine();
echo 'line_prefix='.substr($line, 0, 40)."\n";
echo 'routing_off='.($line === 'happ://routing/off' ? 'yes' : 'no')."\n";
echo 'geoip_url='.config('xui.happ_routing.geoip_url')."\n";
echo 'geosite_url='.config('xui.happ_routing.geosite_url')."\n";
echo 'direct_sites='.implode(',', config('xui.happ_routing.direct_sites', []))."\n";
echo 'direct_ip='.implode(',', config('xui.happ_routing.direct_ip', []))."\n";

if ($line !== 'happ://routing/off') {
    $b64 = (string) preg_replace('#^happ://routing/(onadd|add)/#', '', $line);
    $j = json_decode((string) base64_decode($b64, true), true);
    if (is_array($j)) {
        echo 'profile_Geoipurl='.($j['Geoipurl'] ?? '')."\n";
        echo 'profile_Geositeurl='.($j['Geositeurl'] ?? '')."\n";
        echo 'profile_DirectSites='.implode(',', $j['DirectSites'] ?? [])."\n";
        echo 'profile_DirectIp_has_geoip='.(in_array('geoip:ru', $j['DirectIp'] ?? [], true) ? 'yes' : 'no')."\n";
    }
}

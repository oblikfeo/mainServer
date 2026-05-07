#!/usr/bin/env bash
# One-shot check/fix on hub: drop geosite from .env, clear admin rules if they force geo, clear caches.
set -eu
cd /var/www/vpn-hub

echo "=== git HEAD ==="
git rev-parse --short HEAD

echo "=== .env: HAPP_* and geosite ==="
grep -nE '^HAPP_|geosite|category-ru' .env || true

echo "=== happ_routing_rules (DB) ==="
php artisan tinker --execute='dump(App\Models\AppSetting::getValue("happ_routing_rules"));'

if grep -qE '^HAPP_DIRECT_SITES=.*geosite' .env; then
  bak=".env.bak-happ-$(date +%Y%m%d%H%M%S)"
  cp -a .env "$bak"
  echo "Backup: $bak"
  sed -i '/^HAPP_DIRECT_SITES=/d' .env
  echo "Removed HAPP_DIRECT_SITES from .env (fallback = config/xui.php default list)."
fi

_rules_out="$(php artisan tinker --execute='echo App\Models\AppSetting::getValue("happ_routing_rules") ?? "";' 2>/dev/null | tail -1)"
if echo "$_rules_out" | grep -qiE 'geosite:|geoip:'; then
  echo "happ_routing_rules in DB contains geosite/geoip вЂ” clearing key."
  php artisan tinker --execute='App\Models\AppSetting::forgetKey("happ_routing_rules");'
fi

php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo "=== HAPP_DIRECT_SITES in .env after ==="
grep -n '^HAPP_DIRECT_SITES=' .env || echo "(unset вЂ” OK)"

echo "=== direct_sites count + sample ==="
php artisan tinker --execute='$s=config("xui.happ_routing.direct_sites"); echo "count=".count($s).PHP_EOL.implode(",", array_slice($s,0,10))."...".PHP_EOL;'

echo "=== happ line must not contain Geoipurl ==="
php artisan tinker --execute='$s=config("xui.happ_routing.direct_sites"); $line=App\Services\Subscription\HappRoutingSubscriptionLine::buildOnAddLine("direct", $s, true, []); $ok=$line===null||(!str_contains($line,"Geoipurl")&&!str_contains($line,"Geositeurl")); echo ($ok?"OK":"BAD").PHP_EOL.(substr($line??"",0,100)).PHP_EOL;'

echo "=== done ==="

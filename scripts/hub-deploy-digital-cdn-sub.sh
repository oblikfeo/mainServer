#!/bin/bash
set -euo pipefail
cd /var/www/vpn-hub

ENV_FILE="/var/www/vpn-hub/.env"
DIGITAL_URI='vless://4311c20d-24f7-4e56-bf12-71bbd76e9c83@nadezhda.digital:443?encryption=none&security=tls&sni=nadezhda.digital&fp=chrome&type=xhttp&path=%2Fapi%2Fv1%2Fupload%2F&host=nadezhda.digital&mode=packet-up&extra=%7B%22path%22%3A%22%2Fapi%2Fv1%2Fupload%2F%22%2C%22seqKey%22%3A%22chunk_id%22%2C%22seqPlacement%22%3A%22query%22%2C%22sessionKey%22%3A%22X-Upload-Token%22%2C%22sessionPlacement%22%3A%22header%22%2C%22uplinkHTTPMethod%22%3A%22GET%22%2C%22xPaddingHeader%22%3A%22X-Client-Version%22%2C%22xPaddingKey%22%3A%22hash%22%2C%22xPaddingMethod%22%3A%22tokenish%22%2C%22xPaddingObfsMode%22%3Atrue%2C%22xPaddingPlacement%22%3A%22queryInHeader%22%2C%22xmux%22%3A%7B%22cMaxLifetimeMs%22%3A300000%2C%22cMaxReuseTimes%22%3A100%2C%22maxConcurrency%22%3A%2216-32%22%2C%22maxConnections%22%3A0%7D%7D'

upsert_env() {
  local key="$1"
  local val="$2"
  if grep -q "^${key}=" "$ENV_FILE"; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
  else
    printf '\n%s=%s\n' "$key" "$val" >> "$ENV_FILE"
  fi
}

upsert_env SUB_DIGITAL_CDN_ENABLED true
upsert_env SUB_DIGITAL_CDN_VLESS_URI "$DIGITAL_URI"
upsert_env 'SUB_DIGITAL_CDN_VLESS_TITLE' '"🇳🇱 Обход глушилок LTE"'
upsert_env SUB_DIGITAL_CDN_VLESS_SUBTITLE ''

php artisan config:clear
php artisan view:clear

php artisan tinker --execute='$n=0; foreach(App\Services\Subscription\SubscriptionExtraShareLines::orderedWithBundle(["vless_entries"=>[]], false) as $l){ if(str_contains($l,"nadezhda.digital")) $n++; } echo "digital_lines=$n\n";'

echo DEPLOY_DIGITAL_CDN_OK

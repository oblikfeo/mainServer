#!/bin/bash
set -euo pipefail
cd /var/www/vpn-hub

ENV_FILE="/var/www/vpn-hub/.env"
CDN_URI='vless://5c96138d-5f12-469b-b93d-8f30bb1f29bc@cdn.nadezhda.space:443?encryption=none&security=tls&sni=cdn.nadezhda.space&fp=chrome&type=xhttp&path=%2Fapi%2Fv1%2Fupload%2F&host=cdn.nadezhda.space&mode=packet-up&extra=%7B%22path%22%3A%22%2Fapi%2Fv1%2Fupload%2F%22%2C%22seqKey%22%3A%22chunk_id%22%2C%22seqPlacement%22%3A%22query%22%2C%22sessionKey%22%3A%22X-Upload-Token%22%2C%22sessionPlacement%22%3A%22header%22%2C%22uplinkHTTPMethod%22%3A%22GET%22%2C%22xPaddingHeader%22%3A%22X-Client-Version%22%2C%22xPaddingKey%22%3A%22hash%22%2C%22xPaddingMethod%22%3A%22tokenish%22%2C%22xPaddingObfsMode%22%3Atrue%2C%22xPaddingPlacement%22%3A%22queryInHeader%22%2C%22xmux%22%3A%7B%22cMaxLifetimeMs%22%3A300000%2C%22cMaxReuseTimes%22%3A100%2C%22maxConcurrency%22%3A%2216-32%22%2C%22maxConnections%22%3A0%7D%7D'

upsert_env() {
  local key="$1"
  local val="$2"
  if grep -q "^${key}=" "$ENV_FILE"; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
  else
    printf '\n%s=%s\n' "$key" "$val" >> "$ENV_FILE"
  fi
}

upsert_env SUB_CDN_ENABLED true
upsert_env SUB_CDN_VLESS_URI "$CDN_URI"
upsert_env 'SUB_CDN_VLESS_TITLE' '"🇫🇮 Обход глушилок LTE"'
upsert_env SUB_CDN_VLESS_SUBTITLE ''
upsert_env SUB_FEED_HWID_IGNORE_IPS '127.0.0.1,::1,158.160.200.205,82.24.19.230,82.40.56.223,195.133.198.100,169.40.15.141'

php artisan config:clear
php artisan view:clear

echo DEPLOY_CDN_OK

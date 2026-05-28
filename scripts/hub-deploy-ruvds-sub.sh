#!/bin/bash
set -euo pipefail
cd /var/www/vpn-hub

git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction

ENV_FILE="/var/www/vpn-hub/.env"
RUVDS_URI='vless://fd62b6cf-640d-4cef-9ab8-bf7ed397f5b2@195.133.198.100:443?security=reality&encryption=none&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=lGu4gSRvqFSQ5z581ii5XK67SZ48EFTDiFzv6YXlOHM&sid=540bc43939cc2abb&flow=xtls-rprx-vision'

upsert_env() {
  local key="$1"
  local val="$2"
  if grep -q "^${key}=" "$ENV_FILE"; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
  else
    printf '\n%s=%s\n' "$key" "$val" >> "$ENV_FILE"
  fi
}

upsert_env SUB_RUVDS_ENABLED true
upsert_env SUB_RUVDS_VLESS_URI "$RUVDS_URI"
upsert_env 'SUB_RUVDS_VLESS_TITLE' '"🇭🇰 Megafon, Tele2, Yota"'
upsert_env SUB_RUVDS_VLESS_SUBTITLE ''
upsert_env 'XUI_FI_VLESS_NAME' '"🇫🇮 Beeline, MTC"'
upsert_env 'XUI_NL_VLESS_NAME' '"🇳🇱 Мобильная сеть [3]"'

php artisan config:clear
php artisan view:clear
php artisan test --filter=SubscriptionExtraShareLinesTest

echo DEPLOY_RUVDS_OK

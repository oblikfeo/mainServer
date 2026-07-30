#!/bin/bash
# Включить узел KZ46 (46.8.43.49, 🇰🇿 Быстрый Wi~~Fi) в подписку Happ.
# Запускать на хабе (Yandex main), после `git pull`.
set -euo pipefail
ENV_FILE="/var/www/vpn-hub/.env"
URI='vless://0ad515e2-9f7b-4a78-8c78-c4bfdf1f279b@46.8.43.49:443?encryption=none&security=reality&type=tcp&sni=www.yandex.ru&fp=chrome&pbk=bLyQClmxSF03ecmLUDkNGoD_haQWor3-6oFj5ArpBAA&sid=1da4375d0e934fa6&spx=%2F'
upsert_env() {
  local key="$1"
  local val="$2"
  if grep -q "^${key}=" "$ENV_FILE"; then
    sudo sed -i "/^${key}=/d" "$ENV_FILE"
  fi
  printf '%s=%s\n' "$key" "$val" | sudo tee -a "$ENV_FILE" >/dev/null
}
upsert_env SUB_KZ46_ENABLED true
upsert_env SUB_KZ46_VLESS_URI "$URI"
upsert_env 'SUB_KZ46_VLESS_TITLE' '"🇰🇿 Быстрый Wi~~Fi"'
upsert_env SUB_KZ46_VLESS_SUBTITLE ''
upsert_env LINK_KZ46_IP 46.8.43.49
upsert_env LINK_KZ46_SSH_USER root
upsert_env LINK_KZ46_SSH_KEY /var/www/vpn-hub/storage/app/ssh/kz46_ed25519
upsert_env LINK_KZ46_CLIENT_TCP_PORT 443
cd /var/www/vpn-hub
php artisan config:clear
php artisan view:clear
echo HUB_DEPLOY_KZ46_OK

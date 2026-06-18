#!/usr/bin/env bash
# Боевой .env после cutover на Yandex CDN: canonical www, WATA URLs, Resend API.
set -euo pipefail

ENV="/var/www/vpn-hub/.env"
BASE="https://www.nadezhda.space"

upsert_env() {
  local key="$1"
  local val="$2"
  if grep -q "^${key}=" "$ENV"; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV"
  else
    printf '\n%s=%s\n' "$key" "$val" >> "$ENV"
  fi
}

upsert_env APP_URL "${BASE}"
upsert_env WATA_WEBHOOK_URL "${BASE}/payments/wata/webhook"
upsert_env WATA_SUCCESS_URL "${BASE}/spasibo"
upsert_env WATA_FAIL_URL "${BASE}/oshibka"
upsert_env HUB_ROLE production_cdn
upsert_env MIRROR_YANDEX_CDN false

if ! grep -q '^RESEND_API_KEY=' "$ENV" && grep -q '^MAIL_PASSWORD=' "$ENV"; then
  pw="$(grep '^MAIL_PASSWORD=' "$ENV" | cut -d= -f2- | tr -d '"' | tr -d "'")"
  [[ -n "$pw" ]] && upsert_env RESEND_API_KEY "$pw"
fi
upsert_env MAIL_MAILER resend

cd /var/www/vpn-hub
sudo -u oblik php artisan config:clear
sudo -u oblik php artisan view:clear
echo "PROD_WWW_ENV_OK app_url=${BASE}"

#!/usr/bin/env bash
# Yandex CDN hub: .env, cron, HWID ignore для origin/CDN.
set -euo pipefail

ENV="/var/www/vpn-hub/.env"
ORIGIN_IP="158.160.200.205"
HOSTKEY_IP="82.24.19.230"

upsert_env() {
  local key="$1"
  local val="$2"
  if grep -q "^${key}=" "$ENV"; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV"
  else
    printf '\n%s=%s\n' "$key" "$val" >> "$ENV"
  fi
}

upsert_env APP_URL "https://nadezhda.space"
upsert_env APP_ENV production
upsert_env APP_DEBUG false
upsert_env HUB_ROLE yandex_cdn_mirror
upsert_env MIRROR_YANDEX_CDN true
upsert_env SUB_FEED_HWID_IGNORE_IPS "127.0.0.1,::1,${ORIGIN_IP},${HOSTKEY_IP},82.24.19.230,82.40.56.223,195.133.198.100,169.40.15.141"

if ! grep -q '^RESEND_API_KEY=' "$ENV" && grep -q '^MAIL_PASSWORD=' "$ENV"; then
  pw="$(grep '^MAIL_PASSWORD=' "$ENV" | cut -d= -f2- | tr -d '"' | tr -d "'")"
  [[ -n "$pw" ]] && upsert_env RESEND_API_KEY "$pw"
fi
upsert_env MAIL_MAILER resend

CRON_LINE='* * * * * cd /var/www/vpn-hub && /usr/bin/php artisan schedule:run >> /dev/null 2>&1'
( crontab -u oblik -l 2>/dev/null | grep -v 'artisan schedule:run' || true; echo "$CRON_LINE" ) | crontab -u oblik -

cd /var/www/vpn-hub
sudo -u oblik php artisan config:clear
sudo -u oblik php artisan view:clear
echo PREP_ENV_CDN_OK

#!/usr/bin/env bash
# Hostkey mirror: cron, .env для нового hub IP, свежий config:clear. Прод Yandex не трогаем.
set -euo pipefail

ENV="/var/www/vpn-hub/.env"
HOSTKEY_IP="82.24.19.230"

if ! command -v crontab >/dev/null 2>&1; then
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -qq
  apt-get install -y -qq cron
  systemctl enable --now cron
fi

upsert_env() {
  local key="$1"
  local val="$2"
  if grep -q "^${key}=" "$ENV"; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV"
  else
    printf '\n%s=%s\n' "$key" "$val" >> "$ENV"
  fi
}

if grep -q '^SUB_FEED_HWID_IGNORE_IPS=' "$ENV"; then
  cur="$(grep '^SUB_FEED_HWID_IGNORE_IPS=' "$ENV" | cut -d= -f2-)"
  if ! echo "$cur" | grep -q "$HOSTKEY_IP"; then
    new="${cur},${HOSTKEY_IP}"
    upsert_env SUB_FEED_HWID_IGNORE_IPS "$new"
  fi
else
  upsert_env SUB_FEED_HWID_IGNORE_IPS "127.0.0.1,::1,${HOSTKEY_IP},195.133.198.100,158.160.158.78,169.40.15.141"
fi

upsert_env APP_ENV production
upsert_env APP_DEBUG false

# Resend API (не SMTP: с Hostkey исходящий 465 к smtp.resend.com часто недоступен)
upsert_env MAIL_MAILER resend
if ! grep -q '^RESEND_API_KEY=' "$ENV"; then
  pw="$(grep '^MAIL_PASSWORD=' "$ENV" 2>/dev/null | cut -d= -f2- | tr -d '"' | tr -d "'")"
  if [ -n "$pw" ]; then
    upsert_env RESEND_API_KEY "$pw"
  fi
fi
if ! sudo -u oblik test -f /var/www/vpn-hub/vendor/resend/resend-php/composer.json 2>/dev/null; then
  sudo -u oblik bash -lc 'cd /var/www/vpn-hub && composer require resend/resend-php:^1.0 --no-interaction' || true
fi

CRON_LINE='* * * * * cd /var/www/vpn-hub && /usr/bin/php artisan schedule:run >> /dev/null 2>&1'
( crontab -u oblik -l 2>/dev/null | grep -v 'artisan schedule:run' || true; echo "$CRON_LINE" ) | crontab -u oblik -

cd /var/www/vpn-hub
sudo -u oblik php artisan config:clear
sudo -u oblik php artisan view:clear
echo PREP_MIRROR_OK

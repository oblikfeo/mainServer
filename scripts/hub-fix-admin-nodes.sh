#!/bin/bash
set -eu
cd /var/www/vpn-hub
ENV_FILE="/var/www/vpn-hub/.env"

upsert_env() {
  local key="$1"
  local val="$2"
  if grep -q "^${key}=" "$ENV_FILE"; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
  else
    printf '\n%s=%s\n' "$key" "$val" >> "$ENV_FILE"
  fi
}

echo '=== path-probe write for www-data ==='
chmod 2775 storage/app/path-probe
sudo -u www-data touch storage/app/path-probe/_write_test && sudo -u www-data rm -f storage/app/path-probe/_write_test && echo WRITE_OK

if [ -f storage/app/ssh/us194_ed25519 ]; then
  chmod 770 storage/app/ssh/us194_ed25519
  chgrp www-data storage/app/ssh/us194_ed25519
  upsert_env LINK_US194_IP 194.110.87.115
  upsert_env LINK_US194_SSH_USER root
  upsert_env LINK_US194_SSH_KEY /var/www/vpn-hub/storage/app/ssh/us194_ed25519
  upsert_env LINK_US194_CLIENT_TCP_PORT 8443
  echo US194_ENV_OK
else
  echo US194_KEY_MISSING
fi

php artisan config:clear
php artisan view:clear

echo '=== probe refresh ==='
timeout 120 php artisan happ:probe-paths --refresh

echo HUB_FIX_OK

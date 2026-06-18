#!/usr/bin/env bash
# Перед cutover: свежие данные с Hostkey. Код — git pull.
set -euo pipefail

APP="/var/www/vpn-hub"
HOSTKEY="${HOSTKEY_HOST:-82.24.19.230}"
SSH_OPTS="-o StrictHostKeyChecking=accept-new -o BatchMode=yes -i /home/oblik/.ssh/id_hostkey_import"

cd "${APP}"
sudo -u oblik git fetch origin main
sudo -u oblik git reset --hard origin/main
sudo -u oblik composer install --no-dev --optimize-autoloader --no-interaction

WORK="/root/hub-import-sync"
mkdir -p "${WORK}"

scp ${SSH_OPTS} "root@${HOSTKEY}:${APP}/.env" "${WORK}/env.production"
ssh ${SSH_OPTS} "root@${HOSTKEY}" "tar -C '${APP}' -czf /tmp/hub-storage.tgz storage"
scp ${SSH_OPTS} "root@${HOSTKEY}:/tmp/hub-storage.tgz" "${WORK}/storage.tgz"
ssh ${SSH_OPTS} "root@${HOSTKEY}" 'bash -s' <<'EOS' > "${WORK}/vpn_hub.sql"
set -euo pipefail
APP="/var/www/vpn-hub"
DB_PASS="$(grep -E '^DB_PASSWORD=' "${APP}/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")"
mysqldump --single-transaction --routines --triggers -u vpn_hub -p"${DB_PASS}" vpn_hub
EOS

DB_PASS_LOCAL="$(grep -E '^DB_PASSWORD=' "${APP}/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")"
grep -v '^DB_PASSWORD=' "${WORK}/env.production" > "${WORK}/env.merge"
echo "DB_PASSWORD=${DB_PASS_LOCAL}" >> "${WORK}/env.merge"
cp "${WORK}/env.merge" "${APP}/.env"

mysql vpn_hub < "${WORK}/vpn_hub.sql"
rm -rf "${APP}/storage"
tar -xzf "${WORK}/storage.tgz" -C "${APP}"
chown -R oblik:www-data "${APP}/storage"
chmod -R ug+rwx "${APP}/storage"

sudo -u oblik php artisan migrate --force
sudo -u oblik php artisan config:clear
sudo -u oblik php artisan view:clear

USERS="$(mysql -N vpn_hub -e 'SELECT COUNT(*) FROM users')"
echo "SYNC_DB_OK users=${USERS}"

#!/usr/bin/env bash
# Данные с Hostkey: .env + storage + mysqldump. Код не трогаем — уже git pull.
set -euo pipefail

HOSTKEY="${HOSTKEY_HOST:-82.24.19.230}"
HOSTKEY_USER="${HOSTKEY_USER:-root}"
APP="/var/www/vpn-hub"
WORK="/root/hub-import"
KEY="/home/oblik/.ssh/id_hostkey_import"
SSH_OPTS="-o StrictHostKeyChecking=accept-new -o BatchMode=yes -i ${KEY}"

mkdir -p /home/oblik/.ssh
chmod 700 /home/oblik/.ssh
if [[ ! -f "${KEY}" ]]; then
  sudo -u oblik ssh-keygen -t ed25519 -N "" -f "${KEY}" -C "yandex-cdn-import"
fi
chmod 600 "${KEY}"
chown oblik:oblik "${KEY}" "${KEY}.pub"

mkdir -p "${WORK}"
rm -rf "${WORK:?}"/*
mkdir -p "${WORK}"

echo "=== import .env from ${HOSTKEY} ==="
scp ${SSH_OPTS} "${HOSTKEY_USER}@${HOSTKEY}:${APP}/.env" "${WORK}/env.production"

echo "=== import storage.tgz ==="
ssh ${SSH_OPTS} "${HOSTKEY_USER}@${HOSTKEY}" "tar -C '${APP}' -czf /tmp/hub-storage.tgz storage"
scp ${SSH_OPTS} "${HOSTKEY_USER}@${HOSTKEY}:/tmp/hub-storage.tgz" "${WORK}/storage.tgz"

echo "=== import mysqldump ==="
ssh ${SSH_OPTS} "${HOSTKEY_USER}@${HOSTKEY}" 'bash -s' <<'EOS' > "${WORK}/vpn_hub.sql"
set -euo pipefail
APP="/var/www/vpn-hub"
if [[ -f /root/.vpn_hub_db_password ]]; then
  DB_PASS="$(tr -d '\r\n' < /root/.vpn_hub_db_password)"
else
  DB_PASS="$(grep -E '^DB_PASSWORD=' "${APP}/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")"
fi
mysqldump --single-transaction --routines --triggers -u vpn_hub -p"${DB_PASS}" vpn_hub
EOS

DB_PASS="$(openssl rand -base64 24 | tr -d '/+=' | head -c 32)"
echo "${DB_PASS}" > /root/.vpn_hub_db_password
chmod 600 /root/.vpn_hub_db_password

mysql -e "CREATE DATABASE IF NOT EXISTS vpn_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "DROP USER IF EXISTS 'vpn_hub'@'localhost';"
mysql -e "CREATE USER 'vpn_hub'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON vpn_hub.* TO 'vpn_hub'@'localhost'; FLUSH PRIVILEGES;"
mysql vpn_hub < "${WORK}/vpn_hub.sql"

cp "${WORK}/env.production" "${APP}/.env"
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" "${APP}/.env"
grep -q '^MIRROR_YANDEX_CDN=' "${APP}/.env" || echo 'MIRROR_YANDEX_CDN=true' >> "${APP}/.env"

rm -rf "${APP}/storage"
tar -xzf "${WORK}/storage.tgz" -C "${APP}"
chown -R oblik:www-data "${APP}/storage"
chmod -R ug+rwx "${APP}/storage"

cd "${APP}"
sudo -u oblik php artisan migrate --force
sudo -u oblik php artisan config:clear
sudo -u oblik php artisan view:clear

USERS="$(mysql -N vpn_hub -e 'SELECT COUNT(*) FROM users')"
echo "IMPORT_HOSTKEY_DATA_OK users=${USERS}"

#!/usr/bin/env bash
# Hostkey: распаковать hub-mirror-latest, поднять БД и storage. Прод Yandex не трогаем.
set -euo pipefail

ARCHIVE="/root/hub-mirror-latest.tar.gz"
APP="/var/www/vpn-hub"
WORK="/root/hub-mirror-restore"

[[ -f "${ARCHIVE}" ]] || { echo "NO_ARCHIVE ${ARCHIVE}"; exit 1; }

rm -rf "${WORK}"
mkdir -p "${WORK}"
tar -xzf "${ARCHIVE}" -C "${WORK}"
STAMP_DIR="$(find "${WORK}" -mindepth 1 -maxdepth 1 -type d | head -1)"
[[ -n "${STAMP_DIR}" ]] || { echo "NO_STAMP_DIR"; exit 1; }

DB_PASS="$(openssl rand -base64 24 | tr -d '/+=' | head -c 32)"
echo "${DB_PASS}" > /root/.vpn_hub_db_password
chmod 600 /root/.vpn_hub_db_password

mysql -e "CREATE DATABASE IF NOT EXISTS vpn_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "DROP USER IF EXISTS 'vpn_hub'@'localhost';"
mysql -e "CREATE USER 'vpn_hub'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON vpn_hub.* TO 'vpn_hub'@'localhost'; FLUSH PRIVILEGES;"
mysql vpn_hub < "${STAMP_DIR}/vpn_hub.sql"

cp "${STAMP_DIR}/env.production" "${APP}/.env"
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" "${APP}/.env"
grep -q '^MIRROR_HOSTKEY=' "${APP}/.env" || echo 'MIRROR_HOSTKEY=true' >> "${APP}/.env"

rm -rf "${APP}/storage"
tar -xzf "${STAMP_DIR}/storage.tgz" -C "${APP}"
chown -R oblik:www-data "${APP}/storage"
chmod -R ug+rwx "${APP}/storage"

cd "${APP}"
sudo -u oblik composer install --no-dev --optimize-autoloader
sudo -u oblik npm ci
sudo -u oblik npm run build
sudo -u oblik php artisan migrate --force
sudo -u oblik php artisan config:clear
sudo -u oblik php artisan view:clear

echo "RESTORE_MIRROR_OK stamp=${STAMP_DIR}"

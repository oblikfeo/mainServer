#!/usr/bin/env bash
# Hostkey (боевой hub): снимок БД + storage + .env. Сервисы не останавливаем.
set -euo pipefail

STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
DEST="/root/hub-mirror/${STAMP}"
ARCHIVE="/root/hub-mirror-latest.tar.gz"
ARCHIVE_OBLIK="/home/oblik/hub-mirror-latest.tar.gz"
APP="/var/www/vpn-hub"

mkdir -p "${DEST}"
echo "BACKUP_STAMP=${STAMP}"

if [[ -f /root/.vpn_hub_db_password ]]; then
  DB_PASS="$(tr -d '\r\n' < /root/.vpn_hub_db_password)"
else
  DB_PASS="$(grep -E '^DB_PASSWORD=' "${APP}/.env" | cut -d= -f2- | tr -d '"' | tr -d "'")"
fi

mysqldump --single-transaction --routines --triggers \
  -u vpn_hub -p"${DB_PASS}" vpn_hub > "${DEST}/vpn_hub.sql"
echo "mysqldump_ok bytes=$(wc -c < "${DEST}/vpn_hub.sql")"

cp -a "${APP}/.env" "${DEST}/env.production"
tar -C "${APP}" -czf "${DEST}/storage.tgz" storage
echo "storage_ok bytes=$(wc -c < "${DEST}/storage.tgz")"

printf '%s\n' "${STAMP}" > "${DEST}/STAMP.txt"
tar -C /root/hub-mirror -czf "${ARCHIVE}" "${STAMP}"
cp -a "${ARCHIVE}" "${ARCHIVE_OBLIK}"
chmod 644 "${ARCHIVE}" "${ARCHIVE_OBLIK}"
ls -lh "${ARCHIVE}"
echo "BACKUP_HOSTKEY_OK archive=${ARCHIVE}"

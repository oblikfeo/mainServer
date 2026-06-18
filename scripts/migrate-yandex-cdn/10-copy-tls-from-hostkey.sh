#!/usr/bin/env bash
# Скопировать LE-сертификат nadezhda.space с Hostkey на Yandex main (для prod nginx до cutover).
set -euo pipefail

ARCHIVE="/root/le-nadezhda-from-hostkey.tar.gz"
test -f "${ARCHIVE}" || { echo "NO_TLS_ARCHIVE ${ARCHIVE}"; exit 1; }

mkdir -p /etc/letsencrypt
tar -xzf "${ARCHIVE}" -C /etc/letsencrypt

test -f /etc/letsencrypt/options-ssl-nginx.conf || \
  cp /usr/lib/python3/dist-packages/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf /etc/letsencrypt/options-ssl-nginx.conf 2>/dev/null || true
test -f /etc/letsencrypt/ssl-dhparams.pem || openssl dhparam -out /etc/letsencrypt/ssl-dhparams.pem 2048

test -f /etc/letsencrypt/live/nadezhda.space/fullchain.pem
echo "COPY_TLS_FROM_HOSTKEY_OK"

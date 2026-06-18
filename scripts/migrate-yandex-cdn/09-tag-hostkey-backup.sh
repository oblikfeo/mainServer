#!/usr/bin/env bash
# Hostkey: пометить роль backup (сервисы не останавливаем).
set -euo pipefail

ENV="/var/www/vpn-hub/.env"

upsert_env() {
  local key="$1"
  local val="$2"
  if grep -q "^${key}=" "$ENV"; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$ENV"
  else
    printf '\n%s=%s\n' "$key" "$val" >> "$ENV"
  fi
}

upsert_env HUB_ROLE backup_hostkey
upsert_env BACKUP_OF production_cdn_yandex
echo "TAG_HOSTKEY_BACKUP_OK ip=82.24.19.230"

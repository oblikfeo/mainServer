#!/usr/bin/env bash
# Smoke prod через CDN (запускать на origin или с ПК curl к публичным URL).
set -euo pipefail

BASE="${SMOKE_BASE:-https://www.nadezhda.space}"

code_login="$(curl -fsS -o /dev/null -w '%{http_code}' "${BASE}/login" || echo fail)"
code_up="$(curl -fsS -o /dev/null -w '%{http_code}' "${BASE}/up" || echo fail)"
code_webhook="$(curl -fsS -o /dev/null -w '%{http_code}' -X POST "${BASE}/payments/wata/webhook" \
  -H 'Content-Type: application/json' -d '{}' || echo fail)"
code_cdn="$(curl -fsSk -o /dev/null -w '%{http_code}' 'https://cdn.nadezhda.space/api/v1/upload/' || echo fail)"

echo "smoke login=${code_login} up=${code_up} webhook_post=${code_webhook} cdn=${code_cdn}"
[[ "${code_login}" == "200" || "${code_login}" == "302" ]] || exit 1
[[ "${code_up}" == "200" ]] || exit 1
# 400 = Laravel (пустое тело), 403 = подпись; 405 = CDN не пропускает POST — см. README CDN POST.
[[ "${code_webhook}" == "400" || "${code_webhook}" == "403" ]] || {
  echo "WARN webhook_post=${code_webhook} — включите POST в Yandex CDN (allowed HTTP methods)"
  exit 1
}
echo "SMOKE_PROD_CDN_OK"

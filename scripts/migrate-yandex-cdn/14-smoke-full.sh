#!/usr/bin/env bash
# Полный smoke после миграции (запуск на origin или с ПК).
set -euo pipefail

BASE="${SMOKE_BASE:-https://nadezhda.space}"
APEX="${SMOKE_APEX:-https://nadezhda.space}"
CDN="${SMOKE_CDN:-https://cdn.nadezhda.space}"
TOKEN="${SMOKE_SUB_TOKEN:-}"

fail=0
ok() { echo "OK  $1"; }
warn() { echo "WARN $1"; fail=1; }
bad() { echo "FAIL $1"; fail=1; }

code="$(curl -fsS -o /dev/null -w '%{http_code}' "${BASE}/" || echo fail)"
[[ "${code}" == "200" ]] && ok "www home ${code}" || bad "www home ${code}"

code="$(curl -fsS -o /dev/null -w '%{http_code}' "${BASE}/login" || echo fail)"
[[ "${code}" == "200" ]] && ok "www login GET ${code}" || bad "www login GET ${code}"

code="$(curl -fsS -o /dev/null -w '%{http_code}' "${BASE}/admin" || echo fail)"
[[ "${code}" == "302" || "${code}" == "200" ]] && ok "admin ${code}" || bad "admin ${code}"

code="$(curl -fsS -o /dev/null -w '%{http_code}' "${BASE}/up" || echo fail)"
[[ "${code}" == "200" ]] && ok "health ${code}" || bad "health ${code}"

code="$(curl -fsSk -o /dev/null -w '%{http_code}' "${CDN}/api/v1/upload/" || echo fail)"
[[ "${code}" == "400" ]] && ok "cdn xhttp GET ${code}" || bad "cdn xhttp GET ${code}"

code="$(curl -fsS -o /dev/null -w '%{http_code}' -X POST "${APEX}/payments/wata/webhook" -H 'Content-Type: application/json' -d '{}' || echo fail)"
[[ "${code}" == "400" || "${code}" == "403" ]] && ok "webhook apex POST ${code}" || warn "webhook apex POST ${code} (ожидали 400/403, не 301/405)"

code="$(curl -fsS -o /dev/null -w '%{http_code}' -X POST "${BASE}/login" -H 'Content-Type: application/x-www-form-urlencoded' -d 'email=a@b.c&password=x' || echo fail)"
[[ "${code}" == "419" || "${code}" == "302" || "${code}" == "422" ]] && ok "login www POST ${code}" || warn "login www POST ${code} — если 405: www через CDN не пропускает POST; смените www на A 158.160.200.205"

if [[ -n "${TOKEN}" ]]; then
  code="$(curl -fsS -o /dev/null -w '%{http_code}' "${BASE}/sub/${TOKEN}" -H 'X-Hwid: smoke-test' || echo fail)"
  [[ "${code}" == "200" ]] && ok "sub+hwid ${code}" || bad "sub+hwid ${code}"
fi

[[ "${fail}" -eq 0 ]] && echo "SMOKE_FULL_OK" || { echo "SMOKE_FULL_WARN"; exit 1; }

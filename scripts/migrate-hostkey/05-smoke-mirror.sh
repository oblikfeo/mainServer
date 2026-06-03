#!/usr/bin/env bash
set -euo pipefail
code_root="$(curl -fsS -o /dev/null -w '%{http_code}' -H 'Host: nadezhda.space' http://127.0.0.1/)"
code_login="$(curl -fsS -o /dev/null -w '%{http_code}' -H 'Host: nadezhda.space' http://127.0.0.1/login)"
users="$(mysql -N vpn_hub -e 'SELECT COUNT(*) FROM users' 2>/dev/null || echo '?')"
echo "smoke root=${code_root} login=${code_login} users=${users}"
[[ "${code_root}" == "200" || "${code_root}" == "302" ]] || exit 1
echo "SMOKE_MIRROR_OK"

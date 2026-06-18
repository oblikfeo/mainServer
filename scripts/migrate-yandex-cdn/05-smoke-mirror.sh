#!/usr/bin/env bash
set -euo pipefail

code_apex="$(curl -fsS -o /dev/null -w '%{http_code}' -H 'Host: nadezhda.space' http://127.0.0.1:8080/login)"
code_www="$(curl -fsS -o /dev/null -w '%{http_code}' -H 'Host: www.nadezhda.space' http://127.0.0.1:8080/login)"
code_up="$(curl -fsS -o /dev/null -w '%{http_code}' -H 'Host: www.nadezhda.space' http://127.0.0.1:8080/up)"
code_cdn="$(curl -fsSk -o /dev/null -w '%{http_code}' -H 'Host: cdn.nadezhda.space' https://127.0.0.1/api/v1/upload/ || echo fail)"
users="$(mysql -N vpn_hub -e 'SELECT COUNT(*) FROM users' 2>/dev/null || echo '?')"
echo "smoke apex=${code_apex} www_login=${code_www} up=${code_up} cdn_xhttp=${code_cdn} users=${users}"
[[ "${code_apex}" == "301" ]] || exit 1
[[ "${code_www}" == "200" || "${code_www}" == "302" ]] || exit 1
[[ "${code_up}" == "200" ]] || exit 1
echo "SMOKE_MIRROR_OK"

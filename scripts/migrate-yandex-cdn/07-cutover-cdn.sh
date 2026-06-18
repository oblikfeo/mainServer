#!/usr/bin/env bash
# Выполнять ПОСЛЕ смены DNS nadezhda.space → Yandex CDN (CNAME как у cdn.nadezhda.space).
# Hostkey 82.24.19.230 остаётся бэкапом, не гасим.
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive
APP="/var/www/vpn-hub"
ORIGIN_IP="158.160.200.205"
HOSTKEY_IP="82.24.19.230"
MOOO_DOMAIN="nadezhda.mooo.com"
CDN_DOMAIN="cdn.nadezhda.space"
HUB_DOMAIN="nadezhda.space"
WEBROOT="/var/www/html"
MOOO_SITE="/etc/nginx/sites-available/mooo-acme"

apt-get install -y -qq certbot openssl

test -f /etc/letsencrypt/options-ssl-nginx.conf || \
  cp /usr/lib/python3/dist-packages/certbot_nginx/_internal/tls_configs/options-ssl-nginx.conf /etc/letsencrypt/options-ssl-nginx.conf 2>/dev/null || true
test -f /etc/letsencrypt/ssl-dhparams.pem || openssl dhparam -out /etc/letsencrypt/ssl-dhparams.pem 2048

CERT_DIR="/etc/letsencrypt/live/${HUB_DOMAIN}"
if [[ ! -f "${CERT_DIR}/fullchain.pem" ]]; then
  certbot certonly --webroot -w "${APP}/public" \
    -d "${HUB_DOMAIN}" -d "www.${HUB_DOMAIN}" \
    --non-interactive --agree-tos -m "admin@${HUB_DOMAIN}" \
    --preferred-challenges http || true
fi
if [[ ! -f "${CERT_DIR}/fullchain.pem" ]]; then
  CERT_DIR="/etc/letsencrypt/live/www.${HUB_DOMAIN}"
fi
[[ -f "${CERT_DIR}/fullchain.pem" ]] || { echo "NO_TLS_CERT ${HUB_DOMAIN}"; exit 1; }

# CDN origin: xhttp + статика (не трогаем пути VPN)
cat >"${MOOO_SITE}" <<NGX
server {
    listen 80;
    listen [::]:80;
    server_name ${MOOO_DOMAIN} ${CDN_DOMAIN};

    location ^~ /.well-known/acme-challenge/ {
        root ${WEBROOT};
        default_type "text/plain";
        try_files \$uri =404;
    }

    location / {
        return 301 https://\$host\$request_uri;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${MOOO_DOMAIN} ${CDN_DOMAIN};

    ssl_certificate     /etc/letsencrypt/live/${MOOO_DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${MOOO_DOMAIN}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    root ${WEBROOT};
    index index.html;

    location /api/v1/upload/ {
        client_max_body_size 0;
        proxy_http_version 1.1;
        proxy_pass http://127.0.0.1:10443/api/v1/upload/;
        proxy_set_header Host ${MOOO_DOMAIN};
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_buffering off;
        proxy_request_buffering off;
        proxy_read_timeout 3600s;
        proxy_send_timeout 3600s;
    }

    # CDN шлёт Host/SNI nadezhda.mooo.com — Laravel здесь же (www/apex через CDN).
    location = / {
        root ${APP}/public;
        rewrite ^ /index.php last;
    }
    location / {
        root ${APP}/public;
        try_files \$uri /index.php?\$query_string;
    }
    location ~ \.php\$ {
        root ${APP}/public;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
NGX

# Laravel hub: apex → www, боевой контент только на www (CDN).
cat >/etc/nginx/sites-available/vpn-hub <<NGX
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${HUB_DOMAIN};

    ssl_certificate     ${CERT_DIR}/fullchain.pem;
    ssl_certificate_key ${CERT_DIR}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    location ^~ /.well-known/acme-challenge/ {
        root ${APP}/public;
        default_type "text/plain";
        try_files \$uri =404;
    }

    location / {
        return 301 https://www.${HUB_DOMAIN}\$request_uri;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.${HUB_DOMAIN};

    root ${APP}/public;
    index index.php;
    add_header X-Hub-Role "production-cdn" always;
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    ssl_certificate     ${CERT_DIR}/fullchain.pem;
    ssl_certificate_key ${CERT_DIR}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    location ^~ /.well-known/acme-challenge/ {
        root ${APP}/public;
        default_type "text/plain";
        try_files \$uri =404;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known).* { deny all; }
}

server {
    listen 80;
    listen [::]:80;
    server_name ${HUB_DOMAIN};
    location ^~ /.well-known/acme-challenge/ {
        root ${APP}/public;
        default_type "text/plain";
        try_files \$uri =404;
    }
    location / {
        return 301 https://www.${HUB_DOMAIN}\$request_uri;
    }
}

server {
    listen 80;
    listen [::]:80;
    server_name www.${HUB_DOMAIN};
    location ^~ /.well-known/acme-challenge/ {
        root ${APP}/public;
        default_type "text/plain";
        try_files \$uri =404;
    }
    location / {
        return 301 https://\$host\$request_uri;
    }
}
NGX

ln -sf "${MOOO_SITE}" /etc/nginx/sites-enabled/mooo-acme
ln -sf /etc/nginx/sites-available/vpn-hub /etc/nginx/sites-enabled/vpn-hub
rm -f /etc/nginx/sites-enabled/vpn-hub-mirror 2>/dev/null || true

nginx -t
systemctl reload nginx

upsert_env() {
  local key="$1"
  local val="$2"
  local env="${APP}/.env"
  if grep -q "^${key}=" "$env"; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$env"
  else
    printf '\n%s=%s\n' "$key" "$val" >> "$env"
  fi
}

upsert_env APP_URL "https://www.${HUB_DOMAIN}"
upsert_env WATA_WEBHOOK_URL "https://www.${HUB_DOMAIN}/payments/wata/webhook"
upsert_env WATA_SUCCESS_URL "https://www.${HUB_DOMAIN}/spasibo"
upsert_env WATA_FAIL_URL "https://www.${HUB_DOMAIN}/oshibka"
upsert_env HUB_ROLE production_cdn
upsert_env MIRROR_YANDEX_CDN false
upsert_env SUB_FEED_HWID_IGNORE_IPS "127.0.0.1,::1,${ORIGIN_IP},${HOSTKEY_IP},82.24.19.230,82.40.56.223,195.133.198.100,169.40.15.141"

cd "${APP}"
sudo -u oblik git pull origin main
sudo -u oblik composer install --no-dev --optimize-autoloader --no-interaction
sudo -u oblik php artisan migrate --force
sudo -u oblik php artisan config:clear
sudo -u oblik php artisan view:clear

code_mooo="$(curl -fsSk -o /dev/null -w '%{http_code}' -H 'Host: ${MOOO_DOMAIN}' https://127.0.0.1/login)"
code_www="$(curl -fsSk -o /dev/null -w '%{http_code}' -H 'Host: www.${HUB_DOMAIN}' https://127.0.0.1/login)"
code_cdn="$(curl -fsSk -o /dev/null -w '%{http_code}' -H 'Host: ${CDN_DOMAIN}' https://127.0.0.1/api/v1/upload/ || echo fail)"
echo "cutover_mooo_login=${code_mooo} cutover_www_login=${code_www} cutover_cdn_xhttp=${code_cdn}"
[[ "${code_mooo}" == "200" || "${code_mooo}" == "302" ]] || exit 1

echo "CUTOVER_CDN_OK cert=${CERT_DIR}"
echo "DNS: www → CNAME Yandex CDN; apex через CDN (если добавлен) → nginx 301 на www"
echo "BACKUP: Hostkey ${HOSTKEY_IP} не трогать — откат = вернуть A-запись на ${HOSTKEY_IP}"

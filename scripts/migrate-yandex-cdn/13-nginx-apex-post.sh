#!/usr/bin/env bash
# Prod nginx: сайт только на apex nadezhda.space, www → 301 на apex.
set -euo pipefail

HUB_DOMAIN="nadezhda.space"
APP="/var/www/vpn-hub"
SITE="/etc/nginx/sites-available/vpn-hub"

if [[ -f /etc/letsencrypt/live/${HUB_DOMAIN}-0001/fullchain.pem ]]; then
  CERT="/etc/letsencrypt/live/${HUB_DOMAIN}-0001"
elif [[ -f /etc/letsencrypt/live/${HUB_DOMAIN}/fullchain.pem ]]; then
  CERT="/etc/letsencrypt/live/${HUB_DOMAIN}"
else
  echo "NO_TLS_CERT ${HUB_DOMAIN}"; exit 1
fi

cat >"${SITE}" <<NGX
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${HUB_DOMAIN};

    root ${APP}/public;
    index index.php;
    add_header X-Hub-Role "production-cdn" always;
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    ssl_certificate     ${CERT}/fullchain.pem;
    ssl_certificate_key ${CERT}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    location ^~ /.well-known/acme-challenge/ {
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
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.${HUB_DOMAIN};

    ssl_certificate     ${CERT}/fullchain.pem;
    ssl_certificate_key ${CERT}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    return 301 https://${HUB_DOMAIN}\$request_uri;
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
        return 301 https://${HUB_DOMAIN}\$request_uri;
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
        return 301 https://${HUB_DOMAIN}\$request_uri;
    }
}
NGX

ln -sf "${SITE}" /etc/nginx/sites-enabled/vpn-hub
nginx -t
systemctl reload nginx
echo "NGINX_APEX_OK canonical=https://${HUB_DOMAIN}"

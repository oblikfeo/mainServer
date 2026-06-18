#!/usr/bin/env bash
# Apex nadezhda.space → Yandex origin: POST webhook WATA без редиректа на www.
# Laravel-страницы для людей по-прежнему 301 → www (CDN или origin).
set -euo pipefail

APP="/var/www/vpn-hub/public"
CERT="/etc/letsencrypt/live/nadezhda.space"
SITE="/etc/nginx/sites-available/vpn-hub"

[[ -f "${CERT}/fullchain.pem" ]] || { echo "NO_CERT ${CERT}"; exit 1; }

cat >"${SITE}" <<'NGX'
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name nadezhda.space;

    ssl_certificate     /etc/letsencrypt/live/nadezhda.space/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/nadezhda.space/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    root /var/www/vpn-hub/public;
    index index.php;

    location ^~ /.well-known/acme-challenge/ {
        default_type "text/plain";
        try_files $uri =404;
    }

    # WATA webhook — только apex (мимо CDN www), без 301.
    location = /payments/wata/webhook {
        try_files $uri /index.php?$query_string;
    }

    location / {
        return 301 https://www.nadezhda.space$request_uri;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.nadezhda.space;

    root /var/www/vpn-hub/public;
    index index.php;
    add_header X-Hub-Role "production-cdn" always;
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    ssl_certificate     /etc/letsencrypt/live/nadezhda.space/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/nadezhda.space/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    location ^~ /.well-known/acme-challenge/ {
        default_type "text/plain";
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known).* { deny all; }
}

server {
    listen 80;
    listen [::]:80;
    server_name nadezhda.space;
    location ^~ /.well-known/acme-challenge/ {
        root /var/www/vpn-hub/public;
        default_type "text/plain";
        try_files $uri =404;
    }
    location / {
        return 301 https://www.nadezhda.space$request_uri;
    }
}

server {
    listen 80;
    listen [::]:80;
    server_name www.nadezhda.space;
    location ^~ /.well-known/acme-challenge/ {
        root /var/www/vpn-hub/public;
        default_type "text/plain";
        try_files $uri =404;
    }
    location / {
        return 301 https://$host$request_uri;
    }
}
NGX

ln -sf "${SITE}" /etc/nginx/sites-enabled/vpn-hub
nginx -t
systemctl reload nginx
echo "NGINX_APEX_POST_OK webhook=/payments/wata/webhook"

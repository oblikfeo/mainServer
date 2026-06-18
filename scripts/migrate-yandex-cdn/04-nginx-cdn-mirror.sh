#!/usr/bin/env bash
# Зеркало :8080 — apex редирект на www, Laravel только на www.
set -euo pipefail

cat >/etc/nginx/sites-available/vpn-hub-mirror <<'NGX'
server {
    listen 8080;
    listen [::]:8080;
    server_name nadezhda.space;
    return 301 http://www.nadezhda.space:8080$request_uri;
}

server {
    listen 8080;
    listen [::]:8080;
    server_name www.nadezhda.space 158.160.200.205 _;
    root /var/www/vpn-hub/public;
    index index.php;
    add_header X-Hub-Mirror "yandex-cdn" always;
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
NGX

ln -sf /etc/nginx/sites-available/vpn-hub-mirror /etc/nginx/sites-enabled/vpn-hub-mirror

MOOO="/etc/nginx/sites-available/mooo-acme"
if [[ -f "${MOOO}" ]] && ! grep -q 'nadezhda.space' "${MOOO}"; then
  sed -i 's/server_name nadezhda.mooo.com cdn.nadezhda.space;/server_name nadezhda.mooo.com cdn.nadezhda.space nadezhda.space www.nadezhda.space;/' "${MOOO}"
fi

nginx -t
systemctl reload nginx
echo "NGINX_CDN_MIRROR_OK port=8080 apex_redirect=www"

#!/usr/bin/env bash
# Hostkey: nginx для зеркала (HTTP по IP, Host header nadezhda.space для проверки).
set -euo pipefail

cat >/etc/nginx/sites-available/vpn-hub-mirror <<'NGX'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name nadezhda.space www.nadezhda.space 82.24.19.230 _;
    root /var/www/vpn-hub/public;
    index index.php;
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
rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true
nginx -t
systemctl reload nginx
echo "NGINX_MIRROR_OK"

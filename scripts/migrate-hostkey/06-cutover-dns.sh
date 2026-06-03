#!/usr/bin/env bash
# Выполнять ТОЛЬКО после смены DNS nadezhda.space -> 82.24.19.230
set -euo pipefail

# WG/VPN уже сняты (_clean_vpn_hostkey.py). Только TLS + prod nginx.
apt-get install -y -qq certbot python3-certbot-nginx
certbot --nginx -d nadezhda.space -d www.nadezhda.space --non-interactive --agree-tos -m admin@nadezhda.space || true

cat >/etc/nginx/sites-available/vpn-hub <<'NGX'
server {
    server_name nadezhda.space www.nadezhda.space;
    root /var/www/vpn-hub/public;
    index index.php;
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
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
    listen 443 ssl;
    listen [::]:443 ssl;
    ssl_certificate /etc/letsencrypt/live/nadezhda.space/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/nadezhda.space/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
}
server {
    listen 80;
    listen [::]:80;
    server_name nadezhda.space www.nadezhda.space;
    return 301 https://$host$request_uri;
}
NGX

ln -sf /etc/nginx/sites-available/vpn-hub /etc/nginx/sites-enabled/vpn-hub
rm -f /etc/nginx/sites-enabled/vpn-hub-mirror
nginx -t && systemctl reload nginx

cd /var/www/vpn-hub
sudo -u oblik git pull origin main
sudo -u oblik composer install --no-dev --optimize-autoloader
sudo -u oblik php artisan migrate --force
sudo -u oblik php artisan config:clear
sudo -u oblik php artisan view:clear

echo "CUTOVER_HOSTKEY_OK — проверьте https://nadezhda.space/"

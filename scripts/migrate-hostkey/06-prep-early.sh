#!/usr/bin/env bash
# До смены @ DNS: TLS для www (уже на Hostkey), prod nginx, hub git pull.
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive
apt-get install -y -qq certbot python3-certbot-nginx

cat >/etc/nginx/sites-available/vpn-hub-prep <<'NGX'
server {
    listen 80;
    listen [::]:80;
    server_name www.nadezhda.space;
    root /var/www/vpn-hub/public;
    index index.php;
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
    root /var/www/vpn-hub/public;
    index index.php;
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
NGX

ln -sf /etc/nginx/sites-available/vpn-hub-prep /etc/nginx/sites-enabled/vpn-hub-prep
rm -f /etc/nginx/sites-enabled/vpn-hub-mirror /etc/nginx/sites-enabled/default 2>/dev/null || true
nginx -t && systemctl reload nginx

# www уже указывает на Hostkey — сертификат можно получить сейчас
certbot certonly --webroot -w /var/www/vpn-hub/public \
  -d www.nadezhda.space \
  --non-interactive --agree-tos -m admin@nadezhda.space \
  --preferred-challenges http

cat >/etc/nginx/sites-available/vpn-hub <<'NGX'
server {
    server_name www.nadezhda.space;
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
    ssl_certificate /etc/letsencrypt/live/www.nadezhda.space/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/www.nadezhda.space/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
}
server {
    listen 80;
    listen [::]:80;
    server_name www.nadezhda.space;
    return 301 https://$host$request_uri;
}
server {
    listen 80;
    listen [::]:80;
    server_name nadezhda.space;
    root /var/www/vpn-hub/public;
    index index.php;
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
NGX

ln -sf /etc/nginx/sites-available/vpn-hub /etc/nginx/sites-enabled/vpn-hub
rm -f /etc/nginx/sites-enabled/vpn-hub-prep
nginx -t && systemctl reload nginx

cd /var/www/vpn-hub
sudo -u oblik git pull origin main || true
sudo -u oblik composer install --no-dev --optimize-autoloader
sudo -u oblik php artisan migrate --force
sudo -u oblik php artisan config:clear
sudo -u oblik php artisan view:clear

echo "EARLY_PREP_OK www_https=ready apex_http=ready_for_when_dns"
echo "When @ -> 82.24.19.230: certbot certonly --webroot -w /var/www/vpn-hub/public -d nadezhda.space --expand --non-interactive"

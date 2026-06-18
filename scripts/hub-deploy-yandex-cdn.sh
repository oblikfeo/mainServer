#!/usr/bin/env bash
# Обновление кода на Yandex CDN origin — только git.
set -euo pipefail
cd /var/www/vpn-hub
sudo -u oblik git fetch origin main
sudo -u oblik git reset --hard origin/main
sudo -u oblik composer install --no-dev --optimize-autoloader --no-interaction
sudo -u oblik npm ci
sudo -u oblik npm run build
sudo -u oblik php artisan migrate --force
sudo -u oblik php artisan config:clear
sudo -u oblik php artisan view:clear
if [[ -f scripts/migrate-yandex-cdn/11-prod-www-env.sh ]]; then
  bash scripts/migrate-yandex-cdn/11-prod-www-env.sh
fi
echo "DEPLOY_OK commit=$(sudo -u oblik git rev-parse --short HEAD)"

#!/usr/bin/env bash
# Код только из GitHub. Без архивов приложения.
set -euo pipefail

APP="/var/www/vpn-hub"
REPO="https://github.com/oblikfeo/mainServer.git"

if ! id -u oblik >/dev/null 2>&1; then
  useradd -m -s /bin/bash oblik
fi

mkdir -p /var/www
chown -R oblik:oblik /var/www

if [[ ! -d "${APP}/.git" ]]; then
  sudo -u oblik git clone "${REPO}" "${APP}"
fi

cd "${APP}"
sudo -u oblik git fetch origin main
sudo -u oblik git reset --hard origin/main
sudo -u oblik composer install --no-dev --optimize-autoloader --no-interaction
sudo -u oblik npm ci
sudo -u oblik npm run build
echo "GIT_DEPLOY_OK commit=$(sudo -u oblik git rev-parse --short HEAD)"

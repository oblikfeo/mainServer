#!/usr/bin/env bash
# Yandex main (158.160.200.205): Laravel-стек. Xray/CDN nginx не трогаем.
set -euo pipefail

export DEBIAN_FRONTEND=noninteractive
APP="/var/www/vpn-hub"
REPO="https://github.com/oblikfeo/mainServer.git"

apt-get update -qq
apt-get install -y -qq \
  mariadb-server \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd \
  git curl unzip ca-certificates cron sqlite3

if ! command -v composer >/dev/null 2>&1; then
  curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

if ! node -v 2>/dev/null | grep -qE '^v20\.|^v22\.'; then
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get install -y -qq nodejs
fi
node -v
npm -v

if ! id -u oblik >/dev/null 2>&1; then
  useradd -m -s /bin/bash oblik
fi

mkdir -p "${APP}"
chown -R oblik:oblik /var/www

if [[ ! -d "${APP}/.git" ]]; then
  sudo -u oblik git clone "${REPO}" "${APP}"
fi

systemctl enable --now mariadb php8.3-fpm cron
echo "BOOTSTRAP_YANDEX_OK"

#!/usr/bin/env bash
set -u
KEY=/var/www/vpn-hub/storage/app/ssh/home_ed25519
sudo -u www-data ssh -i "$KEY" -o BatchMode=yes -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=8 root@185.121.14.153 'echo SSH_OK; systemctl is-active hysteria hysteria-server blitz xray 2>/dev/null; ss -uln | grep :443 || echo no_udp443; ss -tln | grep :443 || echo no_tcp443; pgrep -a hysteria 2>/dev/null | head -2 || true' 2>&1
cd /var/www/vpn-hub && php scripts/hub-smoke-admin-bundles.php 2>&1

#!/bin/bash
set -euo pipefail
cd /var/www/vpn-hub

php scripts/hub-patch-bg31-env.php
php scripts/hub-patch-bg31-admin.php

php artisan config:clear
php artisan view:clear
php artisan test --filter=SubscriptionExtraShareLinesTest

echo DEPLOY_BG31_OK

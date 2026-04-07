# Деплой (кратко)

На сервере с PHP **8.3+** в каталоге приложения:

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Перезапуск PHP-FPM / очередей — по вашей схеме хостинга. Автоматического деплоя в репозитории нет: выкладка — ваш `git pull` (или CI), не из чата.

После миграций появятся таблицы для настроек имени Happ и связи `issued_keys` с подпиской (если ещё не накатывали). Миграция `add_user_id_to_subscriptions` добавляет владельца подписки для личного кабинета. Таблица `purchases` — история покупок в личном кабинете (`/dashboard/purchases`).

Привязка существующей подписки к пользователю с сервера: `php artisan subscription:attach-user {id} email@example.com` (пользователь должен быть зарегистрирован на сайте).

Если на сервере собираете фронт (Vite): `npm ci && npm run build` перед `php artisan view:cache` (или собирайте артефакты в CI и выкладывайте `public/build`).

## Планировщик (лимит устройств по IP)

Команда `subscription:enforce-device-limits` зарегистрирована в расписании (раз в минуту). Нужен системный cron от пользователя веб-сервера:

`* * * * * cd /var/www/vpn-hub && php artisan schedule:run >> /dev/null 2>&1`

В `.env`: `XUI_ENFORCE_DEVICE_LIMITS=true` — при превышении числа уникальных IP (FI+NL) клиенты в 3x-ui отключаются. `XUI_AUTO_REENABLE_CLIENTS=false` — не включать обратно автоматически после снятия превышения.

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

После миграций появятся таблицы для настроек имени Happ и связи `issued_keys` с подпиской (если ещё не накатывали). Миграция `add_user_id_to_subscriptions` добавляет владельца подписки для личного кабинета.

Привязка существующей подписки к пользователю с сервера: `php artisan subscription:attach-user {id} email@example.com` (пользователь должен быть зарегистрирован на сайте).

Если на сервере собираете фронт (Vite): `npm ci && npm run build` перед `php artisan view:cache` (или собирайте артефакты в CI и выкладывайте `public/build`).

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

После миграций появятся таблицы для настроек имени Happ и связи `issued_keys` с подпиской (если ещё не накатывали).

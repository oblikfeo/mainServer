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

## Почта (Resend)

В проекте уже настроен транспорт `resend` (Laravel). На сервере в `.env`:

- `MAIL_MAILER=resend`
- `RESEND_API_KEY=...`
- `MAIL_FROM_ADDRESS=no-reply@nadezhda.space` (или другой адрес на домене, который Verified в Resend)
- `MAIL_FROM_NAME="Nadezhda"` (опционально)

Быстрый тест после деплоя:

`php artisan mail:test you@example.com`

## Лимит устройств (Happ HWID + 3x-ui limitIp)

Подписка `/sub/{token}`: при `SUBSCRIPTION_FEED_REQUIRE_HWID=true` (по умолчанию) приложение Happ шлёт заголовок `X-Hwid`; хаб сохраняет до `devices` разных отпечатков (sha256), остальные получают 403. Новым клиентам в панели выставляется `limitIp = devices` на FI и NL.

После деплоя для **уже созданных** подписок один раз: `php artisan subscription:sync-panel-limit-ip`

Сброс привязки устройств (поддержка): `php artisan subscription:clear-bound-hwid {id}`

Тест без Happ: в `.env` временно `SUBSCRIPTION_FEED_REQUIRE_HWID=false` или запрос с заголовком `X-Hwid: test`.

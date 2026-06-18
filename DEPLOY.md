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

Используем **HTTP API Resend**, не SMTP. В `.env` на проде:

- `MAIL_MAILER=resend`
- `RESEND_API_KEY=re_...` (ключ из панели Resend; **не** класть только в `MAIL_PASSWORD`)
- `MAIL_FROM_ADDRESS=support@nadezhda.space` (адрес на verified-домене в Resend)
- `MAIL_FROM_NAME="Nadezhda"` (опционально)

Пакет обязателен: `composer require resend/resend-php` (уже в `composer.json`; после `git pull` — `composer install`).

**Не ставить** `MAIL_MAILER=smtp` + `smtp.resend.com:465` на hub Hostkey (`82.24.19.230`): исходящий SMTP с VPS часто **таймаутится**, письма quick-buy/WATA падают в лог (`Connection timed out` к `ssl://smtp.resend.com:465`). На старом Yandex SMTP иногда работал — при копировании `.env` на cutover это не заметили.

Строки `MAIL_HOST` / `MAIL_PORT` / `MAIL_PASSWORD` для Resend **не нужны** при `MAIL_MAILER=resend` (можно оставить, Laravel их не использует).

Быстрый тест после деплоя:

`php artisan mail:test you@example.com`

## Лимит устройств (Happ HWID + 3x-ui limitIp)

Подписка `/sub/{token}`: при `SUBSCRIPTION_FEED_REQUIRE_HWID=true` (по умолчанию) приложение Happ шлёт заголовок `X-Hwid`; хаб сохраняет до `devices` разных отпечатков (sha256), остальные получают 403. Новым клиентам в панели выставляется `limitIp = devices` на FI и NL.

После деплоя для **уже созданных** подписок один раз: `php artisan subscription:sync-panel-limit-ip`

Сброс привязки устройств (поддержка): `php artisan subscription:clear-bound-hwid {id}`

Тест без Happ: в `.env` временно `SUBSCRIPTION_FEED_REQUIRE_HWID=false` или запрос с заголовком `X-Hwid: test`.

## Hub на Yandex CDN (prod)

| Параметр | Значение |
|----------|----------|
| Origin | `158.160.200.205:443`, Host/SNI `nadezhda.mooo.com` |
| Canonical URL | `https://www.nadezhda.space` (`APP_URL`, WATA_*_URL) |
| CDN edge | `www.nadezhda.space`, `cdn.nadezhda.space` |
| Apex `@` | A → Hostkey backup → 301 на www (Resend/`support@` — apex OK) |

После cutover: `bash scripts/migrate-yandex-cdn/11-prod-www-env.sh` и smoke `12-smoke-prod-cdn.sh`.

**Yandex CDN:** в ресурсе включить **POST** (и PUT/PATCH для форм Laravel). По умолчанию только GET/HEAD/OPTIONS → webhook WATA и `/buy/pay` отдают 405 на edge.

В ЛК WATA webhook: `https://www.nadezhda.space/payments/wata/webhook`.

Опционально для ЛК (понятное имя устройства): при запросе подписки можно передать один из заголовков `X-Happ-Device-Name`, `X-Device-Name`, `Happ-Device`, `X-Device-Model` — значение попадёт в карточку «Привязанные устройства». Стандартный Happ обычно шлёт только `X-Hwid`; для Android модель часто выводится из User-Agent (например `SM-S918B`), для iPhone в UA обычно нет «iPhone 14» — без своего заголовка от клиента будет только «iPhone» / «iPad».

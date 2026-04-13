# mainServer — VPN Hub (Laravel)

Головной сервер: пользователи (Breeze), админка `/admin` (логин из `.env`), дальше — ключи и связки.

## Локальная разработка

Требования: PHP 8.3+, Composer, Node 20+, расширения PHP для Laravel.

```bash
cp .env.example .env
php artisan key:generate
composer install
npm ci
npm run build
php artisan migrate
php artisan serve
```

В `.env` задайте `DB_*`, затем **`ADMIN_USERNAME`** и **`ADMIN_PASSWORD`** для входа в админку.

## Почта (Resend)

Для отправки писем через Resend выставьте в `.env`:

- `MAIL_MAILER=resend`
- `RESEND_API_KEY=...`
- `MAIL_FROM_ADDRESS=no-reply@nadezhda.space` (адрес на verified-домене в Resend)

Тестовая отправка:

`php artisan mail:test you@example.com`

## Деплой на сервер

На ВМ (пример путь `/var/www/vpn-hub`):

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:clear
php artisan view:clear
```

Файл **`.env` на сервере не из репозитория** — создаётся вручную один раз (можно скопировать с локальной машины без коммита).

## Репозиторий

`https://github.com/oblikfeo/mainServer`

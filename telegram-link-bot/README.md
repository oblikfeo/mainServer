# Telegram-привязка (поллер для NLtest)

Сайт (**mainServer**, Laravel): хранит сессию и код, отдаёт `POST /api/internal/telegram/link/claim` с Bearer-токеном из `TELEGRAM_LINK_INTERNAL_API_TOKEN`.

Эта папка — небольшой **Node-процесс**, который можно держать на машине NLtest (см. `Доступы3/readme.md`) или другой, откуда доступен HTTPS до боевого сайта.

## Что нужно до запуска

1. BotFather: создать бота, получить `TELEGRAM_BOT_TOKEN`.
2. То же значение **`TELEGRAM_LINK_INTERNAL_API_TOKEN`** в `.env` приложения Laravel и в переменных окружения этого процесса.
3. В `.env` приложения указать **`TELEGRAM_LINK_BOT_USERNAME`** — имя бота **без** `@` (иначе пользователь не получит ссылку в профиле).
4. **`TELEGRAM_LINK_SITE_URL`** — базовый URL сайта HTTPS, доступный с машины NLtest до API (обычно `https://nadezhda.space`).

## Поллинг и webhook

При старте скрипт вызывает **`deleteWebhook`**, чтобы работал **getUpdates**. Если у бота был настроен webhook — он будет снят.

## Установка на сервере (пример)

```bash
cd /opt/telegram-link-bot
# скопировать poll.mjs и package.json из этого репозитория (каталог mainServer/telegram-link-bot/)
npm install  # не обязательно — без зависимостей
```

Переменные в `systemd` unit или в `.env` рядом с процессом:

- `TELEGRAM_BOT_TOKEN`
- `TELEGRAM_LINK_SITE_URL`
- `TELEGRAM_LINK_INTERNAL_API_TOKEN`

Запуск: `node poll.mjs` или `npm start`.

## Проверка

В ЛК (подтверждённая почта) → Профиль → «Получить ссылку на бота» → открыть ссылку → в чате придёт 6 цифр → ввести в форму на сайте.

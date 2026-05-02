# Telegram-бот «Надежда»

Сайт (**mainServer**, Laravel): привязка `/start` с токеном из ЛК, UTM deep links, internal API для бота, рассылки через вызов HTTP бота.

Процесс **Node.js**: HTTPS + webhook, постоянное меню, поддержка (пересылка в группу + ответ reply), приём `POST /internal/notify` от Laravel.

## Переменные окружения

**Бот**

| Переменная | Назначение |
|------------|------------|
| `TELEGRAM_BOT_TOKEN` | Токен BotFather |
| `TELEGRAM_LINK_SITE_URL` | Базовый URL сайта (`https://…`), без `/` в конце |
| `TELEGRAM_LINK_INTERNAL_API_TOKEN` | Тот же Bearer, что `TELEGRAM_LINK_INTERNAL_API_TOKEN` в `.env` Laravel |
| `TELEGRAM_BOT_INCOMING_SECRET` | Тот же секрет, что `TELEGRAM_BOT_INCOMING_SECRET` в Laravel (вызов рассылок) |
| `TELEGRAM_WEBHOOK_BASE_URL` | Публичный базовый URL этого сервиса (`https://bot.…`), без `/` в конце; webhook = `{base}/telegram/webhook` |
| `PORT` | Порт HTTP (по умолчанию `3850`) |
| `TELEGRAM_SUPPORT_GROUP_ID` | ID группы поддержки (отрицательный для супергрупп), бот должен быть в группе |
| `TELEGRAM_ADMIN_TELEGRAM_IDS` | Telegram user id админов через запятую (как `TELEGRAM_ADMIN_TELEGRAM_IDS` в Laravel) |

**Laravel (дополнительно к существующим)**

| Переменная | Назначение |
|------------|------------|
| `TELEGRAM_CABINET_MIRROR_URL` | Зеркало / вход в ЛК для кнопки «Личный кабинет» (по умолчанию `APP_URL`) |
| `TELEGRAM_BOT_NOTIFY_BASE_URL` | Базовый URL сервиса бота для `TelegramOutreach` (тот же хост, что webhook) |
| `TELEGRAM_BOT_INCOMING_SECRET` | Секрет для `Authorization: Bearer …` при вызове бота |
| `TELEGRAM_ADMIN_TELEGRAM_IDS` | Список admin Telegram user id через запятую |

## Команды администратора (скрытые)

- `/ndstats` — статистика привязок (через API сайта)
- `/ndbroadcast` — затем одно сообщение (текст с форматированием и/или фото) — массовая рассылка
- `/ndcancel` — отменить ожидание текста рассылки

## Шаблоны рассылок

Файл `templates.json` — ID для вызова из Laravel (`TelegramOutreach::notifyChat`, `template_id`). Переменные в фигурных скобках: `{date}`, `{url_lk}`, `{amount}`, `{new_date}`, `{reminder}` и т.д.

## Запуск

```bash
npm ci
node server.mjs
```

Ранее использовался long polling (`poll.mjs`); по ТЗ используется **webhook** — нужен HTTPS и корректный `TELEGRAM_WEBHOOK_BASE_URL`.

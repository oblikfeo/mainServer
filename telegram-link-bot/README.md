# telegram-link-bot

`npm ci` → `node server.mjs`

Env: `TELEGRAM_BOT_TOKEN`, `TELEGRAM_LINK_SITE_URL` (канонический публичный URL того же Laravel, где создаётся привязка), `TELEGRAM_LINK_INTERNAL_API_TOKEN` (**тот же** строковый Bearer, что `TELEGRAM_LINK_INTERNAL_API_TOKEN` в `.env` приложения); при необходимости `TELEGRAM_BOT_INCOMING_SECRET`, `TELEGRAM_WEBHOOK_BASE_URL` (HTTPS; если пусто — только long polling), `PORT`, `TELEGRAM_SUPPORT_GROUP_ID`, `TELEGRAM_ADMIN_TELEGRAM_IDS`.

**Важно:** на один и тот же `TELEGRAM_BOT_TOKEN` не может одновременно работать несколько процессов `getUpdates` (long polling на разных машинах, или polling + второй процесс на сервере) — Telegram вернёт 409 Conflict, часть сообщений может теряться. Должен остаться ровно один активный режим получения апдейтов.

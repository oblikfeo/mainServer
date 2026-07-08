# telegram-link-bot

`npm ci` → `node server.mjs`

Env: `TELEGRAM_BOT_TOKEN`, `TELEGRAM_LINK_SITE_URL` (канонический публичный URL того же Laravel, где создаётся привязка), `TELEGRAM_LINK_INTERNAL_API_TOKEN` (**тот же** строковый Bearer, что `TELEGRAM_LINK_INTERNAL_API_TOKEN` в `.env` приложения); при необходимости `TELEGRAM_BOT_INCOMING_SECRET`, `TELEGRAM_WEBHOOK_BASE_URL` (HTTPS; если пусто — только long polling), `PORT`, `TELEGRAM_SUPPORT_GROUP_ID`, `TELEGRAM_ADMIN_TELEGRAM_IDS`.

**Важно:** на один и тот же `TELEGRAM_BOT_TOKEN` не может одновременно работать несколько процессов `getUpdates` (long polling на разных машинах, или polling + второй процесс на сервере) — Telegram вернёт 409 Conflict, часть сообщений может теряться. Должен остаться ровно один активный режим получения апдейтов.

## Где развёрнут (прод, на 08.07.2026)

Бот работает на **AlphaVPS cdn-egress `82.118.235.92`** (hostname `forCDN`, тот же сервер, что и WG-egress цепочки CDN).

| | |
|--|--|
| Каталог | `/opt/telegram-link-bot/` — **не git**, выкладка копированием (`scp server.mjs`) |
| systemd | `telegram-link-bot.service` (`Restart=always`), env-файл `/etc/telegram-link-bot.env` |
| Runtime | Node v20, режим **long polling** (`TELEGRAM_WEBHOOK_BASE_URL` пуст), HTTP `:3850` |
| SSH-ключ | `nadezhdaVPN/servers/alphavps/cdn-egress/ssh-key-cdn-egress-ed25519`, `root@82.118.235.92` |

Процедура деплоя кода и диагностика — в `nadezhdaVPN/servers/alphavps/cdn-egress/README.md` (раздел «Telegram-бот»). При рестарте long-polling процесс глушится systemd по таймауту (`status=9/KILL`) — это ожидаемо.

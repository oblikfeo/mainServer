<?php

return [
    /** Имя бота без @ — для ссылки t.me/{username}?start=… */
    'link_bot_username' => env('TELEGRAM_LINK_BOT_USERNAME', ''),
    /**
     * Bearer-токен для внутренних вызовов API (бот → Laravel).
     */
    'link_internal_api_token' => env('TELEGRAM_LINK_INTERNAL_API_TOKEN'),
    /** Время жизни привязочной сессии в минутах */
    'link_session_ttl_minutes' => (int) env('TELEGRAM_LINK_SESSION_TTL_MINUTES', 15),
    /**
     * Публичный URL ЛК / зеркала для кнопки «Личный кабинет» (ТЗ п.4, 7.4).
     */
    'cabinet_mirror_url' => (string) env('TELEGRAM_CABINET_MIRROR_URL', env('APP_URL', '')),
    /**
     * Базовый URL процесса бота (HTTPS), куда Laravel шлёт рассылки (ТЗ п.3).
     * Например https://bot.example.com — без завершающего слэша; путь /internal/notify добавляется в TelegramOutreach.
     */
    'bot_notify_base_url' => (string) env('TELEGRAM_BOT_NOTIFY_BASE_URL', ''),
    /** Общий секрет: Laravel → заголовок Authorization при вызове бота */
    'bot_incoming_secret' => (string) env('TELEGRAM_BOT_INCOMING_SECRET', ''),
    /** Список Telegram user id администраторов (через запятую) для скрытых команд / API (ТЗ п.5) */
    'admin_telegram_user_ids' => array_values(array_filter(array_map(
        static fn (string $v): int => (int) trim($v),
        explode(',', (string) env('TELEGRAM_ADMIN_TELEGRAM_IDS', ''))
    ), static fn (int $v) => $v > 0)),
];

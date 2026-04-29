<?php

return [
    /** Имя бота без @ — для ссылки t.me/{username}?start=… */
    'link_bot_username' => env('TELEGRAM_LINK_BOT_USERNAME', ''),
    /**
     * Bearer-токен для вызова POST /api/internal/telegram/link/claim с NLtest (поллер бота).
     * Сгенерируйте длинную случайную строку и продублируйте в .env на приложении и на сервере бота.
     */
    'link_internal_api_token' => env('TELEGRAM_LINK_INTERNAL_API_TOKEN'),
    /** Время жизни привязочной сессии в минутах */
    'link_session_ttl_minutes' => (int) env('TELEGRAM_LINK_SESSION_TTL_MINUTES', 15),
];

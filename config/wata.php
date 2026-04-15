<?php

return [
    /**
     * Боевой API: https://api.wata.pro/api/h2h/
     * Sandbox API: https://api-sandbox.wata.pro/api/h2h/
     */
    'base_url' => rtrim((string) env('WATA_BASE_URL', 'https://api.wata.pro/api/h2h/'), '/').'/',

    /** Bearer access token из ЛК терминала (H2H). */
    'access_token' => (string) env('WATA_ACCESS_TOKEN', ''),

    /** Публичный идентификатор терминала (нужен для виджета, если решим его использовать). */
    'terminal_public_id' => (string) env('WATA_TERMINAL_PUBLIC_ID', ''),

    /**
     * Куда редиректить пользователя после оплаты (можно переопределять в запросе на links).
     * По умолчанию используем наши /spasibo и /oshibka.
     */
    'success_url' => (string) env('WATA_SUCCESS_URL', rtrim((string) env('APP_URL', ''), '/').'/spasibo'),
    'fail_url' => (string) env('WATA_FAIL_URL', rtrim((string) env('APP_URL', ''), '/').'/oshibka'),

    /** URL webhook на нашей стороне (для справки/конфигурации в ЛК). */
    'webhook_url' => (string) env('WATA_WEBHOOK_URL', rtrim((string) env('APP_URL', ''), '/').'/payments/wata/webhook'),
];


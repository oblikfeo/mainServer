<?php

return [
    'base_url' => rtrim((string) env('PLATEGA_BASE_URL', 'https://app.platega.io/'), '/').'/',

    'merchant_id' => (string) env('PLATEGA_MERCHANT_ID', ''),

    'secret' => (string) env('PLATEGA_SECRET', ''),

    /**
     * Callback на хаб (указать в ЛК Platega → Настройки → Callback URLs).
     */
    'webhook_url' => (string) env(
        'PLATEGA_WEBHOOK_URL',
        rtrim((string) env('APP_URL', ''), '/').'/payments/platega/webhook'
    ),

    /** Куда вернуть пользователя после оплаты / отмены (страница Platega). */
    'return_url' => (string) env(
        'PLATEGA_RETURN_URL',
        'https://t.me/'.ltrim((string) env('TELEGRAM_LINK_BOT_USERNAME', 'nadezhda_tehsup'), '@')
    ),
    'failed_url' => (string) env(
        'PLATEGA_FAILED_URL',
        'https://t.me/'.ltrim((string) env('TELEGRAM_LINK_BOT_USERNAME', 'nadezhda_tehsup'), '@')
    ),

    /**
     * Методы оплаты Platega (как у Wata: СБП + карта).
     * @see https://docs.platega.io/
     */
    'payment_methods' => [
        'sbp' => 2,
        'card' => 11,
    ],
];

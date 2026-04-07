<?php

return [
    'brand_name' => env('MARKETING_BRAND_NAME', 'Надежда'),
    'telegram_url' => env('MARKETING_TELEGRAM_URL', 'https://t.me/'),
    'support_email' => env('MARKETING_SUPPORT_EMAIL', ''),
    /** Дата публикации оферты (строка, напр. 07.04.2026). Пусто — текущая дата на сервере. */
    'offer_published_at' => env('MARKETING_OFFER_PUBLISHED_AT', ''),
    'offer_executor_name' => env('MARKETING_OFFER_EXECUTOR_NAME', ''),
    'offer_executor_inn' => env('MARKETING_OFFER_EXECUTOR_INN', ''),
    /** Если пусто — подставляется support_email */
    'offer_executor_email' => env('MARKETING_OFFER_EXECUTOR_EMAIL', ''),
];

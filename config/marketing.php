<?php

return [
    'brand_name' => env('MARKETING_BRAND_NAME', 'Надежда'),
    /** Общая ссылка на Telegram (лендинг и т.п.). По умолчанию техподдержка @nadezhda_tehsup */
    'telegram_url' => env('MARKETING_TELEGRAM_URL', 'https://t.me/nadezhda_tehsup'),
    /** Если задано — используется в блоке «Поддержка» вместо telegram_url */
    'telegram_support_url' => env('MARKETING_TELEGRAM_SUPPORT_URL') ?: env('MARKETING_TELEGRAM_URL', 'https://t.me/nadezhda_tehsup'),
    /** Опционально: общая почта поддержки (футер). Персональные данные не подставляйте в репозиторий. */
    'support_email' => env('MARKETING_SUPPORT_EMAIL', ''),
    /** Дата публикации оферты (строка, напр. 07.04.2026). Пусто — текущая дата на сервере. */
    'offer_published_at' => env('MARKETING_OFFER_PUBLISHED_AT', ''),
    'apps' => [
        'ios_url' => env('MARKETING_IOS_APP_URL', 'https://apps.apple.com/app/happ-proxy-utility/id6504287215'),
        'android_url' => env('MARKETING_ANDROID_APP_URL', 'https://play.google.com/store/apps/details?id=com.happproxy'),
        'desktop_url' => env('MARKETING_DESKTOP_APP_URL', 'https://www.happ.su/main/ru'),
    ],
];

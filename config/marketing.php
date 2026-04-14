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

    /**
     * Тарифы: главная и ЛК «Оплата» (один источник правды).
     *
     * @var list<array{
     *   id: string,
     *   kind: 'solo'|'family',
     *   title: string,
     *   meta: string,
     *   aria_id: string,
     *   rows: list<array{
     *     period: string,
     *     amount: string,
     *     sub: string|null,
     *     stack?: bool,
     *     badge?: string|null
     *   }>
     * }>
     */
    'tariffs' => [
        [
            'id' => 'solo',
            'kind' => 'solo',
            'title' => 'Для себя',
            'meta' => '2 устройства',
            'aria_id' => 'tariff-solo-title',
            'rows' => [
                ['period' => '1 месяц', 'amount' => '250', 'sub' => null],
                ['period' => '3 месяца', 'amount' => '600', 'sub' => 'Выгода 150&nbsp;₽'],
                ['period' => '6 месяцев', 'amount' => '990', 'sub' => '165&nbsp;₽/мес<br>как чашка кофе'],
            ],
        ],
        [
            'id' => 'family',
            'kind' => 'family',
            'title' => 'Для семьи',
            'meta' => '5 устройств',
            'aria_id' => 'tariff-family-title',
            'rows' => [
                ['period' => '1 месяц', 'amount' => '550', 'sub' => null],
                ['period' => '3 месяца', 'amount' => '1350', 'sub' => 'Выгода 300&nbsp;₽'],
                [
                    'period' => '6 месяцев',
                    'amount' => '2400',
                    'sub' => 'Максимальная выгода',
                    'stack' => true,
                    'badge' => 'Выбор семей',
                ],
            ],
        ],
    ],

    'payment_notes' => [
        'Оплата через СБП или банковской картой РФ.',
        'Без автопродлений: мы не списываем деньги втихую. Вы сами продлеваете доступ.',
        'Гарантия: вернём оплату в течение 24 часов, если сервис не заработал.',
    ],
];

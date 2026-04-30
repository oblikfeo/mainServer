<?php

return [
    'brand_name' => env('MARKETING_BRAND_NAME', 'Надежда'),
    /** Общая ссылка на Telegram (лендинг и т.п.). По умолчанию техподдержка @nadezhda_tehsup */
    'telegram_url' => env('MARKETING_TELEGRAM_URL', 'https://t.me/nadezhda_tehsup'),
    /** Если задано — используется в блоке «Поддержка» вместо telegram_url */
    'telegram_support_url' => env('MARKETING_TELEGRAM_SUPPORT_URL') ?: env('MARKETING_TELEGRAM_URL', 'https://t.me/nadezhda_tehsup'),
    /**
     * Публичный URL сайта для кнопки в строке профиля Happ (#profile-web-page-url).
     * Пусто — берётся из APP_URL.
     */
    'subscription_site_url' => env('MARKETING_SUBSCRIPTION_SITE_URL', ''),
    /**
     * Раньше: общий текст #announce. Сейчас в /sub не используется — только персональные строки (см. subscription_announce_line_*).
     */
    'subscription_announce' => env(
        'MARKETING_SUBSCRIPTION_ANNOUNCE',
        ''
    ),

    /**
     * Персональный #announce для /sub/{token}: две строки (устройства + срок).
     * Выключить: MARKETING_SUBSCRIPTION_ANNOUNCE_PERSONALIZE=false
     */
    'subscription_announce_personalize' => filter_var(
        env('MARKETING_SUBSCRIPTION_ANNOUNCE_PERSONALIZE', true),
        FILTER_VALIDATE_BOOLEAN
    ),

    /** Первая строка анонса Happ. Плейсхолдеры: {used}, {max} */
    'subscription_announce_line_devices' => env(
        'MARKETING_SUBSCRIPTION_ANNOUNCE_LINE_DEVICES',
        'Привязанные устройства: {used}/{max}'
    ),

    /** Вторая строка. Плейсхолдер: {value} — например «12 дней», «истекло», «—» */
    'subscription_announce_line_days' => env(
        'MARKETING_SUBSCRIPTION_ANNOUNCE_LINE_DAYS',
        'Дней до окончания: {value}'
    ),

    /** Если в БД нет даты окончания (expiry_ms = 0) */
    'subscription_announce_value_no_expiry' => env('MARKETING_SUBSCRIPTION_ANNOUNCE_VALUE_NO_EXPIRY', '—'),

    /** Срок по БД уже прошёл */
    'subscription_announce_value_expired' => env('MARKETING_SUBSCRIPTION_ANNOUNCE_VALUE_EXPIRED', 'истекло'),

    /**
     * Цвет иконки кнопки «сайт» в Happ (color-profile → profileWebPageIconColor), формат #RRGGBBAA как в доке Happ.
     * Фирменный оранжевый лендинга (views2 --mock-primary). Пустой .env — не передавать color-profile.
     *
     * @see https://www.happ.su/main/dev-docs/app-management
     */
    'subscription_profile_web_icon_color' => env('MARKETING_SUBSCRIPTION_PROFILE_WEB_ICON_COLOR', '#FF4D00FF'),

    /** Опционально: общая почта поддержки (футер). Персональные данные не подставляйте в репозиторий. */
    'support_email' => env('MARKETING_SUPPORT_EMAIL', ''),
    /** Дата публикации оферты (строка, напр. 07.04.2026). Пусто — текущая дата на сервере. */
    'offer_published_at' => env('MARKETING_OFFER_PUBLISHED_AT', ''),
    'apps' => [
        'ios_url' => env('MARKETING_IOS_APP_URL', 'https://apps.apple.com/ru/app/happ-proxy-utility-plus/id6746188973'),
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
     *   pricing_hint?: string|null,
     *   rows: list<array{
     *     period: string,
     *     amount: string,
     *     sub: string|null,
     *     stack?: bool,
     *     badge?: string|null,
     *     note?: string|null,
     *   }>
     * }>
     */
    'tariffs' => [
        [
            'id' => 'solo',
            'kind' => 'solo',
            'title' => 'Для себя',
            'meta' => '2 устройства',
            'pricing_hint' => '2 устройства',
            'aria_id' => 'tariff-solo-title',
            'rows' => [
                ['period' => '1 месяц', 'amount' => '290', 'sub' => null],
                ['period' => '3 месяца', 'amount' => '700', 'sub' => 'Выгода 170&nbsp;₽'],
                ['period' => '6 месяцев', 'amount' => '1190', 'sub' => 'Всего 198&nbsp;₽/мес', 'note' => 'как чашка кофе'],
            ],
        ],
        [
            'id' => 'family',
            'kind' => 'family',
            'title' => 'Для семьи',
            'meta' => 'до 5 устройств',
            'pricing_hint' => 'До 5 устройств',
            'aria_id' => 'tariff-family-title',
            'rows' => [
                ['period' => '1 месяц', 'amount' => '650', 'sub' => null],
                ['period' => '3 месяца', 'amount' => '1600', 'sub' => 'Выгода 350&nbsp;₽'],
                [
                    'period' => '6 месяцев',
                    'amount' => '2800',
                    'sub' => '≈&nbsp;467&nbsp;₽/мес',
                    'stack' => true,
                    'badge' => 'Выбор семей',
                    'note' => 'Максимальная выгода',
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

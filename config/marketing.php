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
     * Раньше: общий текст #announce. Сейчас в /sub не используется — только блок устройств при персонализации (subscription_announce_line_devices).
     */
    'subscription_announce' => env(
        'MARKETING_SUBSCRIPTION_ANNOUNCE',
        ''
    ),

    /**
     * Персональный #announce для /sub/{token}: только «Привязанные устройства: used/max» (шаблон ниже).
     * Выключить: MARKETING_SUBSCRIPTION_ANNOUNCE_PERSONALIZE=false
     */
    'subscription_announce_personalize' => filter_var(
        env('MARKETING_SUBSCRIPTION_ANNOUNCE_PERSONALIZE', true),
        FILTER_VALIDATE_BOOLEAN
    ),

    /**
     * Первая строка #announce: подсказка про вход на сайт (кнопка «ⓘ» / профиль в Happ). Пусто — не показывать.
     */
    'subscription_announce_cabinet_hint' => env(
        'MARKETING_SUBSCRIPTION_ANNOUNCE_CABINET_HINT',
        'Для входа в личный кабинет на сайте используйте кнопку ⓘ'
    ),

    /** Анонс Happ — строка «Привязанные устройства» (после подсказки). Плейсхолдеры: {used}, {max} */
    'subscription_announce_line_devices' => env(
        'MARKETING_SUBSCRIPTION_ANNOUNCE_LINE_DEVICES',
        'Привязанные устройства: {used}/{max}'
    ),

    /** Анонс Happ — вторая строка (срок). Плейсхолдер: {days} */
    'subscription_announce_line_expiry' => env(
        'MARKETING_SUBSCRIPTION_ANNOUNCE_LINE_EXPIRY',
        'Дней до окончания подписки: {days}'
    ),

    /**
     * Анонс Happ — опциональная строка с {site}. В поле announce URL не становится гиперссылкой.
     * Вход в ЛК только через #profile-web-page-url (одноразовая ссылка по токену, см. happ_cabinet_link_enabled).
     */
    'subscription_announce_line_site' => env(
        'MARKETING_SUBSCRIPTION_ANNOUNCE_LINE_SITE',
        ''
    ),

    /**
     * К расчёту «нужно продление» (query intent=renew в URL профиля и логика анонса): также по исчерпанию трафика по фиду.
     * По умолчанию выключено — из-за суммирования по узлам возможны ложные срабатывания; достаточно срока.
     */
    'happ_renew_check_traffic' => filter_var(
        env('HAPP_RENEW_CHECK_TRAFFIC', false),
        FILTER_VALIDATE_BOOLEAN
    ),

    /**
     * Убрать персональный #announce (устройства, дни, админский текст), когда нужно продление.
     */
    'subscription_announce_suppress_when_needs_renewal' => filter_var(
        env('MARKETING_ANNOUNCE_SUPPRESS_WHEN_NEEDS_RENEWAL', true),
        FILTER_VALIDATE_BOOLEAN
    ),

    /** Если не пусто — одна строка announce при исчерпании вместо полного молчания. */
    'subscription_happ_exhausted_announce_fallback' => env(
        'MARKETING_HAPP_EXHAUSTED_ANNOUNCE_FALLBACK',
        ''
    ),

    /**
     * Ссылки Happ на вход в ЛК по токену подписки (/auth/via-token/{token}). Выключить: false.
     * Если подписка без user_id — по-прежнему открывается публичный сайт (MARKETING_SUBSCRIPTION_SITE_URL / APP_URL).
     */
    'happ_cabinet_link_enabled' => filter_var(
        env('HAPP_CABINET_LINK_ENABLED', true),
        FILTER_VALIDATE_BOOLEAN
    ),

    /**
     * Цвет иконки кнопки «сайт» в Happ (color-profile → profileWebPageIconColor), формат #RRGGBBAA как в доке Happ.
     * Фирменный оранжевый лендинга (views2 --mock-primary). Пустой .env — не передавать color-profile.
     *
     * @see https://www.happ.su/main/dev-docs/app-management
     */
    'subscription_profile_web_icon_color' => env('MARKETING_SUBSCRIPTION_PROFILE_WEB_ICON_COLOR', '#FF4D00FF'),

    /**
     * Happ: режим отображения пинга серверов в подписке.
     * icon — галочка/крестик; time — миллисекунды. Пустой HAPP_PING_RESULT — не передавать.
     *
     * @see https://www.happ.su/main/dev-docs/app-management
     */
    'happ_ping_result' => strtolower(trim((string) env('HAPP_PING_RESULT', 'icon'))),

    /** Happ: автопинг списка серверов при открытии приложения (после обновления подписки). */
    'happ_subscription_ping_onopen_enabled' => filter_var(
        env('HAPP_SUBSCRIPTION_PING_ONOPEN_ENABLED', true),
        FILTER_VALIDATE_BOOLEAN
    ),

    /** Опционально: ping-type (proxy, proxy-head, tcp, icmp). Пусто — не передавать. */
    'happ_ping_type' => strtolower(trim((string) env('HAPP_PING_TYPE', ''))),

    /** URL для проверки в режиме via Proxy (check-url-via-proxy). */
    'happ_check_url_via_proxy' => trim((string) env('HAPP_CHECK_URL_VIA_PROXY', '')),

    /** Опционально: общая почта поддержки (футер). Персональные данные не подставляйте в репозиторий. */
    'support_email' => env('MARKETING_SUPPORT_EMAIL', ''),
    /** Дата публикации оферты (строка, напр. 07.04.2026). Пусто — текущая дата на сервере. */
    'offer_published_at' => env('MARKETING_OFFER_PUBLISHED_AT', ''),
    'apps' => [
        'ios_url' => env('MARKETING_IOS_APP_URL', 'https://apps.apple.com/ru/app/happ-proxy-utility/id6783623643'),
        /** Альтернативный iOS-клиент (RU App Store), дополнительно к Happ. */
        'ios_alt_url' => env('MARKETING_IOS_ALT_APP_URL', 'https://apps.apple.com/ru/app/incy/id6756943388'),
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

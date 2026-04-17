<?php

/**
 * Панели 3x-ui: создание клиентов и объединённая подписка для Happ.
 *
 * Заголовок и строка #subscription-userinfo в теле — суммарный трафик по подписке (0/100 в Happ).
 * Вторая строка в Happ: после title в фрагменте через «?» (см. happ.su app-management). Режим задаётся vless_server_description_format.
 * В 3x-ui на каждом inbound свой totalGB = intdiv(quota_в_байтах, count(bundle_order)).
 */
return [
    'panel_username' => env('XUI_PANEL_USER', ''),
    'panel_password' => env('XUI_PANEL_PASSWORD', ''),

    'bundle_order' => ['fi', 'nl'],

    'nodes' => [
        'fi' => [
            'panel_base' => rtrim((string) env('XUI_FI_BASE', ''), '/'),
            'sub_origin' => rtrim((string) env('XUI_FI_SUB_ORIGIN', ''), '/'),
            'pub_host' => env('XUI_FI_PUB_HOST', ''),
            'inbound_id' => (int) env('XUI_FI_INBOUND_ID', 1),
            'client_email_prefix' => env('XUI_FI_EMAIL_PREFIX', 'fi'),
            'vless_display_name' => env('XUI_FI_VLESS_NAME') ?: 'Франция 🇫🇷',
        ],
        'nl' => [
            'panel_base' => rtrim((string) env('XUI_NL_BASE', ''), '/'),
            'sub_origin' => rtrim((string) env('XUI_NL_SUB_ORIGIN', ''), '/'),
            'pub_host' => env('XUI_NL_PUB_HOST', ''),
            'inbound_id' => (int) env('XUI_NL_INBOUND_ID', 2),
            'client_email_prefix' => env('XUI_NL_EMAIL_PREFIX', 'nl'),
            'vless_display_name' => env('XUI_NL_VLESS_NAME') ?: 'Нидерланды 🇳🇱',
        ],
    ],

    /**
     * Подпись под заголовком узла в Happ (вместо «VLESS»). Пусто — не добавлять вторую часть.
     * @see https://www.happ.su/main/dev-docs/app-management
     */
    'vless_server_description' => trim((string) env('XUI_VLESS_SERVER_DESCRIPTION', 'белый список')),

    /** b64 = #Title?serverDescription=<base64>; dual = #Title?подпись. @see happ.su app-management */
    'vless_server_description_format' => strtolower(trim((string) env('XUI_VLESS_SD_FORMAT', 'b64'))),

    /** Публичная ссылка подписки: {app_url}/sub/{token} */
    /** Имя профиля в Happ (до 25 символов): заголовок и #profile-title в теле */
    'sub_profile_title' => env('SUB_PROFILE_TITLE', 'nadezhda VPN'),
    'sub_profile_update_hours' => env('SUB_PROFILE_UPDATE_HOURS', '12'),
    'sub_output_b64' => env('SUB_OUTPUT_B64', '0') === '1',

    /** Кэш запросов к панелям на странице «Отчёт» (секунды). */
    'report_traffic_cache_ttl' => (int) env('XUI_REPORT_TRAFFIC_CACHE_TTL', 60),

    /** Кэш блока «уникальные IP по подписке» на отчёте (секунды). */
    'report_connection_cache_ttl' => (int) env('XUI_REPORT_CONNECTION_CACHE_TTL', 60),

    /**
     * GET /sub/{token}: требовать заголовок HWID от Happ и хранить до devices уникальных отпечатков.
     * Отключите (false), если тестируете curl без X-Hwid.
     */
    'feed_require_hwid' => filter_var(env('SUBSCRIPTION_FEED_REQUIRE_HWID', true), FILTER_VALIDATE_BOOL),

    /**
     * Happ: правила обхода прокси (Direct) через профиль routing в подписке.
     *
     * @see https://www.happ.su/main/dev-docs/routing
     */
    'happ_routing' => [
        'enabled' => filter_var(env('HAPP_ROUTING_ENABLED', true), FILTER_VALIDATE_BOOL),
        /** true = happ://routing/onadd/... (активировать при получении) */
        'onadd' => filter_var(env('HAPP_ROUTING_ONADD', true), FILTER_VALIDATE_BOOL),
        /** Имя профиля в Happ (короткое) */
        'profile_name' => env('HAPP_ROUTING_PROFILE_NAME', 'direct'),
        /**
         * Список записей для DirectSites (синтаксис как у Xray: full:, domain:, geosite: …).
         * geosite:category-ru — все российские сервисы идут мимо VPN (ВК, Ozon, ВБ, Яндекс и т.д.).
         * 2ip.ru — проверка «без VPN».
         */
        'direct_sites' => array_values(array_filter(array_map('trim', explode(',', (string) env(
            'HAPP_DIRECT_SITES',
            'geosite:category-ru,domain:2ip.ru'
        ))))),
    ],
];

<?php

/**
 * Панели 3x-ui: создание клиентов и объединённая подписка для Happ.
 *
 * Заголовок и строка #subscription-userinfo в теле — суммарный трафик по подписке (0/100 в Happ).
 * Вторая строка в Happ: после title в фрагменте через «?» (см. happ.su app-management). Режим задаётся vless_server_description_format.
 * В 3x-ui на каждом inbound свой totalGB = intdiv(quota_в_байтах, count(bundle_order)).
 */
return [
    /**
     * Глобальные креды 3x-ui — fallback, если для ноды не задан свой пользователь/пароль.
     * Исторически FI и NL делались под одной учёткой, WiFi (Hostkey) — с собственной.
     * Для новых/разнородных панелей ставьте XUI_<KEY>_USER / XUI_<KEY>_PASSWORD.
     */
    'panel_username' => env('XUI_PANEL_USER', ''),
    'panel_password' => env('XUI_PANEL_PASSWORD', ''),

    'bundle_order' => ['wifi', 'fi', 'nl'],

    'nodes' => [
        'wifi' => [
            'panel_base' => rtrim((string) env('XUI_WIFI_BASE', ''), '/'),
            'panel_username' => env('XUI_WIFI_USER') ?: env('XUI_PANEL_USER', ''),
            'panel_password' => env('XUI_WIFI_PASSWORD') ?: env('XUI_PANEL_PASSWORD', ''),
            'sub_origin' => rtrim((string) env('XUI_WIFI_SUB_ORIGIN', ''), '/'),
            'pub_host' => env('XUI_WIFI_PUB_HOST', ''),
            'inbound_id' => (int) env('XUI_WIFI_INBOUND_ID', 3),
            'client_email_prefix' => env('XUI_WIFI_EMAIL_PREFIX', 'wifi2'),
            'client_flow' => env('XUI_WIFI_FLOW', ''),
            'vless_display_name' => env('XUI_WIFI_VLESS_NAME') ?: '🇭🇰 Высокая скорость Wi-Fi',
            'vless_server_description' => env('XUI_WIFI_SERVER_DESC', 'прямое подключение'),
            'reality_sid' => env('XUI_WIFI_REALITY_SID', '0123456789abcdef'),
        ],
        'fi' => [
            'panel_base' => rtrim((string) env('XUI_FI_BASE', ''), '/'),
            'panel_username' => env('XUI_FI_USER') ?: env('XUI_PANEL_USER', ''),
            'panel_password' => env('XUI_FI_PASSWORD') ?: env('XUI_PANEL_PASSWORD', ''),
            'sub_origin' => rtrim((string) env('XUI_FI_SUB_ORIGIN', ''), '/'),
            'pub_host' => env('XUI_FI_PUB_HOST', ''),
            'inbound_id' => (int) env('XUI_FI_INBOUND_ID', 1),
            'client_email_prefix' => env('XUI_FI_EMAIL_PREFIX', 'fi'),
            'client_flow' => env('XUI_FI_FLOW', 'xtls-rprx-vision'),
            'vless_display_name' => env('XUI_FI_VLESS_NAME') ?: '🇫🇮 LTE Город 1 🏙️',
            'vless_server_description' => env('XUI_FI_SERVER_DESC', 'белый список'),
            'reality_sid' => env('XUI_FI_REALITY_SID', ''),
        ],
        'nl' => [
            'panel_base' => rtrim((string) env('XUI_NL_BASE', ''), '/'),
            'panel_username' => env('XUI_NL_USER') ?: env('XUI_PANEL_USER', ''),
            'panel_password' => env('XUI_NL_PASSWORD') ?: env('XUI_PANEL_PASSWORD', ''),
            'sub_origin' => rtrim((string) env('XUI_NL_SUB_ORIGIN', ''), '/'),
            'pub_host' => env('XUI_NL_PUB_HOST', ''),
            'inbound_id' => (int) env('XUI_NL_INBOUND_ID', 2),
            'client_email_prefix' => env('XUI_NL_EMAIL_PREFIX', 'nl'),
            'client_flow' => env('XUI_NL_FLOW', 'xtls-rprx-vision'),
            'vless_display_name' => env('XUI_NL_VLESS_NAME') ?: '🇳🇱 LTE Город 2 🏙️',
            'vless_server_description' => env('XUI_NL_SERVER_DESC', 'белый список'),
            'reality_sid' => env('XUI_NL_REALITY_SID', ''),
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

<?php

/**
 * Панели 3x-ui: создание клиентов и объединённая подписка для Happ.
 *
 * Заголовок и строка #subscription-userinfo в теле — суммарный трафик по подписке (0/100 в Happ).
 * Вторая строка в Happ: после title в фрагменте через «?» (см. happ.su app-management). Режим задаётся vless_server_description_format.
 * В 3x-ui на каждом inbound свой totalGB = полный лимит подписки в байтах (без деления по связкам).
 */
return [
    /**
     * Глобальные креды 3x-ui — fallback, если для ноды не задан свой пользователь/пароль.
     * Для новых/разнородных панелей ставьте XUI_<KEY>_USER / XUI_<KEY>_PASSWORD.
     */
    'panel_username' => env('XUI_PANEL_USER', ''),
    'panel_password' => env('XUI_PANEL_PASSWORD', ''),

    'bundle_order' => ['fi', 'nl'],

    /**
     * Общие для всех подписок доп. узлы (конец тела после FI/NL/hy2 Blitz).
     * Секреты лучше переопределять через .env (SUB_EXTRA_*); значения по умолчанию — тестовый NL VPS.
     *
     * @var array{
     *   enabled: bool,
     *   vless_uri: string,
     *   vless_title: string,
     *   vless_subtitle: string,
     *   hy2_uri: string,
     *   hy2_fragment: string
     * }
     */
    'sub_extra' => [
        'enabled' => filter_var(env('SUB_EXTRA_ENABLED', true), FILTER_VALIDATE_BOOL),
        'vless_uri' => trim((string) env(
            'SUB_EXTRA_VLESS_URI',
            'vless://a75de635-c516-46fd-810a-75e3b5af3d1c@naivefortestoblik.mooo.com:443?encryption=none&flow=xtls-rprx-vision&security=reality&sni=www.microsoft.com&fp=chrome&pbk=fjXvEUa1SV_r8m0TchKnDPkyD4dvdaF_4Xh5hBCbeSg&sid=0b5333d5a3bca9ea&type=tcp&headerType=none'
        )),
        'vless_title' => trim((string) env('SUB_EXTRA_VLESS_TITLE', '🇩🇪 Домашний интернет 1 ⚡')),
        'vless_subtitle' => trim((string) env('SUB_EXTRA_VLESS_SUBTITLE', '')),
        'hy2_uri' => trim((string) env(
            'SUB_EXTRA_HY2_URI',
            'hysteria2://:8B0FTAJYAajOR0pBR0bqGyw@naivefortestoblik.mooo.com:443?sni=naivefortestoblik.mooo.com'
        )),
        'hy2_fragment' => trim((string) env('SUB_EXTRA_HY2_FRAGMENT', '🇺🇸 Домашний интернет 2 ⚡')),
    ],

    'nodes' => [
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
            'vless_server_description' => env('XUI_FI_SERVER_DESC', 'LTE Финляндия — основной узел'),

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
            'vless_server_description' => env('XUI_NL_SERVER_DESC', 'LTE Нидерланды — запасной узел'),

            'reality_sid' => env('XUI_NL_REALITY_SID', ''),
        ],
    ],

    /**
     * Подпись под заголовком узла в Happ (вместо «VLESS»). Пусто — не добавлять вторую часть.
     * @see https://www.happ.su/main/dev-docs/app-management
     */
    'vless_server_description' => trim((string) env('XUI_VLESS_SERVER_DESCRIPTION', 'LTE · стабильное соединение')),


    /** b64 = #Title?serverDescription=<base64>; dual = #Title?подпись. @see happ.su app-management */
    'vless_server_description_format' => strtolower(trim((string) env('XUI_VLESS_SD_FORMAT', 'b64'))),

    /** Публичная ссылка подписки: {app_url}/sub/{token} */
    /** Имя профиля в Happ (до 25 символов): заголовок и #profile-title в теле */
    'sub_profile_title' => env('SUB_PROFILE_TITLE', 'Nadezhda 🧭 VPN'),
    'sub_profile_update_hours' => env('SUB_PROFILE_UPDATE_HOURS', '12'),
    'sub_output_b64' => env('SUB_OUTPUT_B64', '0') === '1',

    /**
     * Формат GET /sub/{token}: uri — hy2+vless строки (боевой). xray_json — тело с Xray JSON (см. SUB_JSON_* в .env).
     * По умолчанию в config: uri, если переменную не задали.
     */
    'sub_feed_format' => strtolower(trim((string) env('SUB_FEED_FORMAT', 'uri'))),

    /** Непустое значение фиксирует meta.serverDescription в JSON-подписке (перекрывает авто-сборку по узлам). */
    'sub_json_meta_server_description' => trim((string) env('SUB_JSON_META_SERVER_DESCRIPTION', '')),

    /** После JSON на новой строке добавить hy2:// при наличии HY2 в подписке. */
    'sub_json_append_hy2_uri' => filter_var(env('SUB_JSON_APPEND_HY2', true), FILTER_VALIDATE_BOOL),

    /**
     * Переопределение пресета routing.rules до основного outbound. null — список «русские сервисы direct» как в образце конкурентов.
     *
     * @var null|list<array<string, mixed>>
     */
    'sub_json_direct_domains' => null,

    /**
     * Как упаковать несколько узлов FI/NL в JSON-подписку:
     * per_node — отдельный JSON на каждый VLESS (как несколько конфигов; свои remarks + meta.serverDescription на узел — ближе к URI-подписке).
     * merged — один профиль и balancer между узлами (в Happ часто выглядит как один туннель / нестабильно).
     */
    'sub_json_bundle_mode' => strtolower(trim((string) env('SUB_JSON_BUNDLE_MODE', 'per_node'))),

    /** true — человекочитаемый JSON (merged); для per_node игнорируется (одна строка на профиль). */
    'sub_json_pretty_print' => filter_var(env('SUB_JSON_PRETTY_PRINT', false), FILTER_VALIDATE_BOOL),

    'sub_gray_subtitles' => [
        'fi' => trim((string) env('SUB_GRAY_FI', '')),
        'nl' => trim((string) env('SUB_GRAY_NL', '')),
        'trial' => trim((string) env('SUB_GRAY_TRIAL', '')),
    ],

    /** Серая строка во фрагменте vless:// / hy2:// (Happ URI); JSON-подпись через meta для JSON не ограничивается. */
    'happ_fragment_subtitle_max_chars' => max(48, min(160, (int) env('HAPP_FRAGMENT_SUBTITLE_MAX_CHARS', 96))),

    /** По умолчанию true: перед JSON добавить hy2+vless строки как в URI-режиме (мобильный Happ часто не парсит JSON из тела URL-подписки). */
    'sub_json_prepend_share_lines' => filter_var(env('SUB_JSON_PREPEND_SHARE_LINES', true), FILTER_VALIDATE_BOOL),

    /**
     * Добавлять в префикс строки vless:// (по умолчанию да). Если false — только hy2 и JSON-профили: часть мобильных клиентов не увидит LTE; на ПК Happ часто добавляет пометку JSON.
     */
    'sub_json_prepend_vless_uris' => filter_var(env('SUB_JSON_PREPEND_VLESS', true), FILTER_VALIDATE_BOOL),

    /**
     * Вкладывать Xray JSON после share-линий. Пустое значение env = auto.
     * Auto: не вкладывать только если prepend содержит vless:// (совпадение с JSON на десктопе Happ).
     * SUB_JSON_EMBED_PROFILES=1|0|true|false|always|never
     */
    'sub_json_embed_profiles_env' => strtolower(trim((string) env('SUB_JSON_EMBED_PROFILES', ''))),

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
     * По умолчанию выключено: в подписку уходит happ://routing/off (см. dev-docs/routing), без загрузки geo-профилей.
     *
     * @see https://www.happ.su/main/dev-docs/routing
     */
    'happ_routing' => [
        'enabled' => filter_var(env('HAPP_ROUTING_ENABLED', false), FILTER_VALIDATE_BOOL),
        /** При enabled=false — первая строка подписки и заголовок routing: happ://routing/off (отключить маршрутизацию в Happ). */
        'send_off_when_disabled' => filter_var(env('HAPP_ROUTING_SEND_OFF_WHEN_DISABLED', true), FILTER_VALIDATE_BOOL),
        /** true = happ://routing/onadd/... (активировать при получении) */
        'onadd' => filter_var(env('HAPP_ROUTING_ONADD', true), FILTER_VALIDATE_BOOL),
        /** Имя профиля в Happ (короткое) */
        'profile_name' => env('HAPP_ROUTING_PROFILE_NAME', 'direct'),
        /**
         * Список записей для DirectSites (синтаксис как у Xray: full:, domain:, keyword: …).
         * В подписке в JSON профиля Happ явно передаются пустые Geoipurl/Geositeurl — иначе клиент подставляет дефолтные URL на .dat.
         * Набор как у типичных конкурентов + доп. суффиксы под реальный трафик (WB: wbbasket.ru не покрывается domain:wildberries.ru).
         */
        'direct_sites' => array_values(array_filter(array_map('trim', explode(',', (string) env(
            'HAPP_DIRECT_SITES',
            implode(',', [
                'domain:mtalk.google.com',
                'domain:push.apple.com',
                'domain:api.push.apple.com',
                'domain:push-apple.com.akadns.net',
                'domain:courier.push.apple.com',
                'domain:mangabuff.ru',
                'domain:yandex.com',
                'domain:yandex.net',
                'domain:yandex.ru',
                'domain:mail.ru',
                'domain:vk.com',
                'domain:vkusvill.ru',
                'domain:ozon.ru',
                'domain:wildberries.ru',
                'domain:wbbasket.ru',
                'domain:wb.ru',
                'domain:tinkoff.ru',
                'domain:gosuslugi.ru',
                'domain:nalog.gov.ru',
                'domain:mos.ru',
                'domain:2gis.com',
                'domain:2gis.ru',
                'domain:2ip.ru',
            ])
        ))))),
    ],
];

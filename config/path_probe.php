<?php

return [
    'cache_ttl' => (int) env('PATH_PROBE_CACHE_TTL', 120),

    'xray_binary' => trim((string) env('PATH_PROBE_XRAY_BINARY', '/usr/local/bin/xray')),

    'socks_port' => (int) env('PATH_PROBE_SOCKS_PORT', 10820),

    'xray_startup_seconds' => (float) env('PATH_PROBE_XRAY_STARTUP_SECONDS', 2.5),

    'probe_timeout_seconds' => (int) env('PATH_PROBE_TIMEOUT_SECONDS', 90),

    'trace_url' => trim((string) env('PATH_PROBE_TRACE_URL', 'https://www.cloudflare.com/cdn-cgi/trace')),

    'speed_url' => trim((string) env(
        'PATH_PROBE_SPEED_URL',
        'https://speed.cloudflare.com/__down?bytes=5000000'
    )),

    /**
     * @var list<array{id: string, extra_key: string, title_key: string}>
     */
    'nodes' => [
        ['id' => 'us194', 'extra_key' => 'sub_extra_us194', 'title_key' => 'vless_title'],
        ['id' => 'bg31', 'extra_key' => 'sub_extra_bg31', 'title_key' => 'vless_title'],
        ['id' => '777', 'extra_key' => 'sub_extra_777', 'title_key' => 'vless_title'],
        ['id' => 'ruvds', 'extra_key' => 'sub_extra_ruvds', 'title_key' => 'vless_title'],
        ['id' => 'nl75', 'extra_key' => 'sub_extra_nl75', 'title_key' => 'vless_title'],
        ['id' => 'cdn', 'extra_key' => 'sub_extra_cdn', 'title_key' => 'vless_title'],
        ['id' => 'digital_cdn', 'extra_key' => 'sub_extra_digital_cdn', 'title_key' => 'vless_title'],
    ],

    /**
     * @var array<string, array{expected_egress?: string, must_not_egress?: string}>
     */
    'egress_rules' => [
        'us194' => [
            'expected_egress' => trim((string) env('PATH_PROBE_US194_EGRESS_IP', '194.110.87.115')),
        ],
        'bg31' => [
            'expected_egress' => trim((string) env('PATH_PROBE_BG31_EGRESS_IP', '31.22.10.250')),
        ],
        '777' => [
            'expected_egress' => trim((string) env('PATH_PROBE_777_EGRESS_IP', '169.40.15.141')),
        ],
        'ruvds' => [
            'must_not_egress' => trim((string) env('PATH_PROBE_RUVDS_MUST_NOT_EGRESS', '195.133.198.100')),
            'expected_egress' => trim((string) env('PATH_PROBE_RUVDS_EGRESS_IP', '')),
        ],
        'nl75' => [
            'expected_egress' => trim((string) env('PATH_PROBE_NL75_EGRESS_IP', '222.167.208.75')),
        ],
        'cdn' => [
            // AlphaVPS Sofia — сменил выключенный Hostkey FI 82.40.56.223 (06.07.2026).
            'expected_egress' => trim((string) env('PATH_PROBE_CDN_EGRESS_IP', '82.118.235.92')),
            'must_not_egress' => trim((string) env('PATH_PROBE_CDN_MUST_NOT_EGRESS', '158.160.200.205')),
        ],
        'digital_cdn' => [
            'expected_egress' => trim((string) env('PATH_PROBE_DIGITAL_CDN_EGRESS_IP', '82.24.19.230')),
        ],
    ],

    /**
     * Фоллбэк-проверка «фронт жив», когда сквозной xhttp-туннель с hub недостоверен
     * по устройству канала (белый CDN не тянет длинный поток с IP ЦОД, но реальные
     * клиенты обслуживаются). Если туннель не поднялся, но фронт отвечает (любой
     * не-5xx HTTP-код) — узел считается рабочим; красный — только когда фронт реально
     * недоступен (нет ответа / 5xx). Ключ — id узла.
     *
     * @var array<string, array{url: string, note: string}>
     */
    'front_fallback' => [
        'digital_cdn' => [
            'url' => trim((string) env(
                'PATH_PROBE_DIGITAL_CDN_FRONT_URL',
                'https://nadezhda.digital/api/v1/upload/?chunk_id=probe'
            )),
            'note' => 'Сквозная xhttp-проба с hub недостоверна: белый CDN (Yandex) не тянет '
                .'длинный поток с IP ЦОД. Доступность подтверждена по CDN-фронту '
                .'(nadezhda.digital) и origin cdn2 82.24.19.230 — реальные клиенты обслуживаются.',
        ],
    ],

    /**
     * Прямая проверка с hub: страница открывается, сайт в сети.
     *
     * @var list<array{id: string, title: string, url: string}>
     */
    'web_pages' => [
        [
            'id' => 'site',
            'title' => 'nadezhda.space',
            'url' => trim((string) env('PATH_PROBE_SITE_URL', 'https://nadezhda.space')),
        ],
        [
            'id' => 'seo',
            'title' => 'nadezhda.info',
            'url' => trim((string) env('PATH_PROBE_SEO_URL', 'https://nadezhda.info')),
        ],
    ],
];

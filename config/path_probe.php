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
            'expected_egress' => trim((string) env('PATH_PROBE_CDN_EGRESS_IP', '82.40.56.223')),
            'must_not_egress' => trim((string) env('PATH_PROBE_CDN_MUST_NOT_EGRESS', '158.160.200.205')),
        ],
        'digital_cdn' => [
            'expected_egress' => trim((string) env('PATH_PROBE_DIGITAL_CDN_EGRESS_IP', '82.24.19.230')),
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

<?php

$trafficWarnTb = (float) env('LINK_TRAFFIC_WARN_TB', 2);
$trafficCritTb = (float) env('LINK_TRAFFIC_CRIT_TB', 3);

return [
    'bundles' => [
        [
            'id' => '777',
            'name' => '777',
            'subtitle' => '169.40.15.141 · 🇧🇬 Быстрый Wi--Fi · shared VLESS',
            'ip' => (string) env('LINK_777_IP', '169.40.15.141'),
            'ssh_user' => (string) env('LINK_777_SSH_USER', 'root'),
            'ssh_private_key' => env('LINK_777_SSH_KEY', ''),
            'client_tcp_port' => (int) env('LINK_777_CLIENT_TCP_PORT', 443),
            'health_profile' => 'home',
        ],
        [
            'id' => 'ruvds',
            'name' => 'RUVDS',
            'subtitle' => '195.133.198.100 · 🇭🇰 МегаФон, Теле2, Йота · общая VLESS',
            'ip' => (string) env('LINK_RUVDS_IP', '195.133.198.100'),
            'ssh_user' => (string) env('LINK_RUVDS_SSH_USER', 'root'),
            'ssh_private_key' => env('LINK_RUVDS_SSH_KEY', ''),
            'client_tcp_port' => (int) env('LINK_RUVDS_CLIENT_TCP_PORT', 443),
            'health_profile' => 'home',
        ],
        [
            'id' => 'fi',
            'name' => 'Связка FI',
            'subtitle' => 'Yandex FI · egress Hostkey',
            'ip' => (string) env('LINK_FI_IP', '158.160.158.78'),
            'ssh_user' => (string) env('LINK_FI_SSH_USER', 'oblik'),
            'ssh_private_key' => env('LINK_FI_SSH_KEY', ''),
            'client_tcp_port' => (int) env('LINK_FI_CLIENT_TCP_PORT', 443),
        ],
    ],

    /*
    | Проверка «онлайн» для статистики: TCP с хаба + по SSH маршрут, :443, Xray, egress HTTPS.
    | Без SSH-ключа остаётся только TCP до client_tcp_port.
    */
    'health' => [
        'cache_ttl' => (int) env('LINK_HEALTH_CACHE_TTL', 30),
        'client_tcp_port' => (int) env('LINK_CLIENT_TCP_PORT', 443),
        'ssh_timeout_seconds' => (int) env('LINK_HEALTH_SSH_TIMEOUT', 22),
    ],

    'tcp_timeout_seconds' => 2,

    'metrics_cache_ttl' => (int) env('LINK_METRICS_CACHE_TTL', 20),

    /*
    | Ручная база трафика для карточек «Статус серверов».
    | Формула: display_bytes + max(0, panel_bytes - panel_base_bytes).
    | Это позволяет «начать отсчёт» от согласованных значений и дальше расти от панели.
    */
    'traffic_baseline' => [
        'fi' => [
            // 264.42 ГБ (данные Hostkey)
            'display_bytes' => (int) env('LINK_TRAFFIC_BASE_FI_BYTES', 264_420_000_000),
            // Текущее значение panel bytes в момент фиксации базы
            'panel_base_bytes' => (int) env('LINK_TRAFFIC_BASE_FI_PANEL_BYTES', 118_689_275_167),
        ],
    ],

    /*
    | Пороги для цвета строк в админке.
    | Подписки (активные): ориентир ~200 на типичный egress 2 vCPU / 4 ГБ RAM под Xray/Reality
    | (реальная цифра зависит от канала и поведения; меняй LINK_KEYS_CAPACITY).
    | Трафик: лимит договорной, пороги в терабайтах (10¹² байт).
    */
    'thresholds' => [
        'keys_capacity' => (int) env('LINK_KEYS_CAPACITY', 200),
        'keys_warn_ratio' => 0.65,
        'keys_crit_ratio' => 0.90,
        'traffic_warn_bytes' => (int) round($trafficWarnTb * 1_000_000_000_000),
        'traffic_crit_bytes' => (int) round($trafficCritTb * 1_000_000_000_000),
    ],
];

<?php

$trafficWarnTb = (float) env('LINK_TRAFFIC_WARN_TB', 2);
$trafficCritTb = (float) env('LINK_TRAFFIC_CRIT_TB', 3);

return [
    'bundles' => [
        [
            'id' => 'wifi',
            'name' => 'Связка WiFi',
            'subtitle' => 'Hostkey · доступы4',
            'ip' => '222.167.208.75',
            'ssh_user' => env('LINK_WIFI_SSH_USER', 'root'),
            'ssh_private_key' => env('LINK_WIFI_SSH_KEY', ''),
            'client_tcp_port' => (int) env('LINK_WIFI_CLIENT_TCP_PORT', 443),
        ],
        [
            'id' => 'nl',
            'name' => 'Связка NL',
            'subtitle' => 'Нидерланды · egress',
            'ip' => '158.160.208.31',
            'ssh_user' => 'ubuntu',
            'ssh_private_key' => env('LINK_NL_SSH_KEY', ''),
            'client_tcp_port' => (int) env('LINK_NL_CLIENT_TCP_PORT', 443),
        ],
        [
            'id' => 'fi',
            'name' => 'Связка FI',
            'subtitle' => 'Финляндия · egress',
            'ip' => '158.160.241.36',
            'ssh_user' => 'oblik',
            'ssh_private_key' => env('LINK_FI_SSH_KEY', ''),
            'client_tcp_port' => (int) env('LINK_FI_CLIENT_TCP_PORT', 443),
        ],
        [
            'id' => 'trial',
            'name' => 'Связка TRIAL',
            'subtitle' => 'Тестовые ключи · Yandex → NLtest',
            'ip' => env('LINK_TRIAL_IP', '158.160.219.3'),
            'ssh_user' => env('LINK_TRIAL_SSH_USER', 'oblik'),
            'ssh_private_key' => env('LINK_TRIAL_SSH_KEY', ''),
            'client_tcp_port' => (int) env('LINK_TRIAL_CLIENT_TCP_PORT', 443),
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
        'nl' => [
            // 88.34 ГБ (данные Hostkey)
            'display_bytes' => (int) env('LINK_TRAFFIC_BASE_NL_BYTES', 88_340_000_000),
            // Текущее значение panel bytes в момент фиксации базы
            'panel_base_bytes' => (int) env('LINK_TRAFFIC_BASE_NL_PANEL_BYTES', 9_798_275_650),
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

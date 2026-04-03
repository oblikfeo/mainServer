<?php

$trafficWarnTb = (float) env('LINK_TRAFFIC_WARN_TB', 2);
$trafficCritTb = (float) env('LINK_TRAFFIC_CRIT_TB', 3);

return [
    'bundles' => [
        [
            'id' => 'nl',
            'name' => 'Связка NL',
            'subtitle' => 'Нидерланды · egress',
            'ip' => '158.160.208.31',
            'ssh_user' => 'ubuntu',
            'ssh_private_key' => env('LINK_NL_SSH_KEY', ''),
            'check_port' => 22,
        ],
        [
            'id' => 'fi',
            'name' => 'Связка FI',
            'subtitle' => 'Финляндия · egress',
            'ip' => '158.160.241.36',
            'ssh_user' => 'oblik',
            'ssh_private_key' => env('LINK_FI_SSH_KEY', ''),
            'check_port' => 22,
        ],
    ],

    'tcp_timeout_seconds' => 2,

    'metrics_cache_ttl' => (int) env('LINK_METRICS_CACHE_TTL', 45),

    /*
    | Пороги для цвета строк в админке.
    | Ключи: ориентир ~200 активных клиентов на типичный egress 2 vCPU / 4 ГБ RAM под Xray/Reality
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

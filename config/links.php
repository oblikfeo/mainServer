<?php

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
];

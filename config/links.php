<?php

return [
    /*
    | Связки (exit / 3x-ui). Порт проверки — TCP: хост «отвечает», если сокет открывается.
    | При блокировке SSH с головного сервера смените check_port на 443 и т.п.
    */
    'bundles' => [
        [
            'id' => 'nl',
            'name' => 'Связка NL',
            'subtitle' => 'Нидерланды · egress',
            'ip' => '158.160.208.31',
            'ssh_user' => 'ubuntu',
            'check_port' => 22,
        ],
        [
            'id' => 'fi',
            'name' => 'Связка FI',
            'subtitle' => 'Финляндия · egress',
            'ip' => '158.160.241.36',
            'ssh_user' => 'oblik',
            'check_port' => 22,
        ],
    ],

    'tcp_timeout_seconds' => 2,
];

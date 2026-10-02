<?php

/**
 * Справочник серверов за узлами подписки — для страницы админки «Узлы подписки».
 * Ключ — хост из share-ссылки SUB_AUTO_* (IP или домен CDN).
 * Сами ссылки и пулы живут в .env (config xui.sub_auto), здесь только подписи.
 */
return [
    '217.217.227.213' => [
        'provider' => 'AlphaVPS',
        'location' => 'Болгария, София',
        'server' => 'new217',
    ],
    '217.217.234.49' => [
        'provider' => 'AlphaVPS',
        'location' => 'Болгария, София (геобазы пишут Нидерланды)',
        'server' => 'new234',
    ],
    '217.217.234.134' => [
        'provider' => 'AlphaVPS',
        'location' => 'Болгария, София (геобазы пишут Нидерланды)',
        'server' => 'sofia3',
    ],
    '169.40.15.141' => [
        'provider' => 'AlphaVPS',
        'location' => 'Болгария, София',
        'server' => 'Sofia1',
    ],
    '193.25.216.18' => [
        'provider' => 'PLAY2GO',
        'location' => 'Швеция, Стокгольм (по факту Хельсинки)',
        'server' => 'newprov',
    ],
    'api.1connection.ru' => [
        'provider' => 'Яндекс CDN → выход AlphaVPS',
        'location' => 'Вход в РФ (CDN), выход Болгария',
        'server' => 'cdn-egress',
        'egress' => '82.118.235.92',
    ],
    'nadezhda.digital' => [
        'provider' => 'Яндекс CDN → выход AlphaVPS',
        'location' => 'Вход в РФ (CDN), выход Болгария',
        'server' => 'digital-cdn-egress',
        'egress' => '185.223.252.83',
    ],
];

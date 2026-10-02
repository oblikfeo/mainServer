<?php

/**
 * Справочник серверов за узлами подписки — для страницы админки «Узлы подписки».
 * Ключ — хост из share-ссылки SUB_AUTO_* (IP или домен CDN).
 * Сами ссылки и пулы живут в .env (config xui.sub_auto), здесь только подписи.
 *
 * location — где сервер физически (замер задержки 02.10.2026: все AlphaVPS
 * в одном ДЦ в Софии, между собой ~0.25 мс). geo — что пишут геобазы (ip-api),
 * google — страну какой видит Google (поле GL на youtube.com с этого IP).
 */
return [
    '217.217.227.213' => [
        'provider' => 'AlphaVPS',
        'location' => '🇧🇬 София',
        'geo' => 'Болгария',
        'google' => 'Болгария',
        'server' => 'new217',
    ],
    '217.217.234.49' => [
        'provider' => 'AlphaVPS',
        'location' => '🇧🇬 София',
        'geo' => 'Нидерланды',
        'google' => 'Болгария',
        'server' => 'new234',
    ],
    '217.217.234.134' => [
        'provider' => 'AlphaVPS',
        'location' => '🇧🇬 София',
        'geo' => 'Нидерланды',
        'google' => 'Россия',
        'server' => 'sofia3',
    ],
    '169.40.15.141' => [
        'provider' => 'AlphaVPS',
        'location' => '🇧🇬 София',
        'geo' => 'Болгария',
        'google' => null,
        'server' => 'sofia1',
    ],
    '193.25.216.18' => [
        'provider' => 'PLAY2GO',
        'location' => '🇸🇪 Швеция',
        'geo' => 'Швеция',
        'google' => 'Швеция',
        'server' => 'newprov',
    ],
    'api.1connection.ru' => [
        'provider' => 'Яндекс CDN → выход AlphaVPS',
        'location' => '🇷🇺 → 🇧🇬 София',
        'geo' => 'Болгария',
        'google' => 'Россия',
        'server' => 'cdn-egress',
        'egress' => '82.118.235.92',
    ],
    'nadezhda.digital' => [
        'provider' => 'Яндекс CDN → выход AlphaVPS',
        'location' => '🇷🇺 → 🇧🇬 София',
        'geo' => 'Болгария',
        'google' => 'Россия',
        'server' => 'digital-cdn-egress',
        'egress' => '185.223.252.83',
    ],
];

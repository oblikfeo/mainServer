<?php

/**
 * Продукты оплаты (тариф -> период) для кабинета.
 * Цены берём из marketing.php, но параметры подписки фиксируем здесь,
 * чтобы не доверять данным из браузера.
 */
return [
    'products' => [
        // solo: 2 устройства
        'solo' => [
            'devices' => 2,
            'rows' => [
                '1 месяц' => ['days' => 30, 'quota_gb' => 100, 'amount_rub' => 290],
                '3 месяца' => ['days' => 90, 'quota_gb' => 300, 'amount_rub' => 700],
                '6 месяцев' => ['days' => 180, 'quota_gb' => 600, 'amount_rub' => 1190],
            ],
        ],
        // family: 5 устройств
        'family' => [
            'devices' => 5,
            'rows' => [
                '1 месяц' => ['days' => 30, 'quota_gb' => 250, 'amount_rub' => 650],
                '3 месяца' => ['days' => 90, 'quota_gb' => 750, 'amount_rub' => 1600],
                '6 месяцев' => ['days' => 180, 'quota_gb' => 1500, 'amount_rub' => 2800],
            ],
        ],
    ],
];


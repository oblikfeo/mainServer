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
                '1 месяц' => ['days' => 30, 'quota_gb' => 200, 'amount_rub' => 250],
                '3 месяца' => ['days' => 90, 'quota_gb' => 600, 'amount_rub' => 600],
                '6 месяцев' => ['days' => 180, 'quota_gb' => 1200, 'amount_rub' => 990],
            ],
        ],
        // family: 5 устройств
        'family' => [
            'devices' => 5,
            'rows' => [
                '1 месяц' => ['days' => 30, 'quota_gb' => 500, 'amount_rub' => 550],
                '3 месяца' => ['days' => 90, 'quota_gb' => 1500, 'amount_rub' => 1350],
                '6 месяцев' => ['days' => 180, 'quota_gb' => 3000, 'amount_rub' => 2400],
            ],
        ],
    ],
];


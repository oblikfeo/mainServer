<?php

/**
 * Продукты оплаты (тариф -> период) для кабинета.
 * Цены берём из marketing.php, но параметры подписки фиксируем здесь,
 * чтобы не доверять данным из браузера.
 */
return [
    /**
     * Быстрая покупка без регистрации (/buy).
     */
    'quick_buy' => [
        'autogen_email_domain' => env('QUICK_BUY_AUTOGEN_EMAIL_DOMAIN', 'buy.nadezhda.local'),
        /** Временно: кнопка в футере /buy — solo 1 мес, списание test amount_rub. Выключить: QUICK_BUY_TEST_PAYMENT_ENABLED=false */
        'test_payment' => [
            'enabled' => filter_var(env('QUICK_BUY_TEST_PAYMENT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'amount_rub' => (int) env('QUICK_BUY_TEST_AMOUNT_RUB', 10),
            'plan' => 'solo',
            'period' => '1 месяц',
            'label' => 'Тест: 1 месяц solo · 10 ₽',
        ],
    ],

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

    /**
     * Продление существующей подписки: к сумме добавляются days / quota_gb / devices (если указано в ряду).
     * План solo|family подбирается в UI по лимиту устройств (см. CabinetPaymentController).
     */
    'renewals' => [
        'solo' => [
            'add_devices' => 0,
            'rows' => [
                '1 месяц' => ['days' => 30, 'quota_gb' => 100, 'amount_rub' => 290],
                '3 месяца' => ['days' => 90, 'quota_gb' => 300, 'amount_rub' => 700],
                '6 месяцев' => ['days' => 180, 'quota_gb' => 600, 'amount_rub' => 1190],
            ],
        ],
        'family' => [
            'add_devices' => 0,
            'rows' => [
                '1 месяц' => ['days' => 30, 'quota_gb' => 250, 'amount_rub' => 650],
                '3 месяца' => ['days' => 90, 'quota_gb' => 750, 'amount_rub' => 1600],
                '6 месяцев' => ['days' => 180, 'quota_gb' => 1500, 'amount_rub' => 2800],
            ],
        ],
    ],
];


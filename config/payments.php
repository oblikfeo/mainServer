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

    /**
     * Бонус в ЛК: +1 устройство к действующей платной подписке (не переносится на новую).
     * Цена: +100 ₽ за каждые 30 календарных дней до конца подписки (1–30 дн. = 100 ₽, 31–60 = 200 ₽ …).
     */
    'bonus_extra_device' => [
        'add_devices' => 1,
        'amount_rub_per_30_days' => 100,
        'day_bucket' => 30,
        /** Шагов тарифа для подписки без даты окончания (3 × 100 ₽ = 300 ₽). */
        'unlimited_steps' => 3,
    ],
];


<?php

return [
    /** Длительность пробного доступа на боевых узлах (XUI expiryTime), часов */
    'hours' => max(1, (int) env('TRIAL_SUBSCRIPTION_HOURS', 3)),

    'quota_gb' => max(1, (int) env('TRIAL_SUBSCRIPTION_QUOTA_GB', 5)),

    'devices' => max(0, (int) env('TRIAL_SUBSCRIPTION_DEVICES', 1)),

    /** Верхняя граница часов при самовыдаче из ЛК (реферальные бонусы и т.п.) */
    'cabinet_hours_cap' => max(1, (int) env('TRIAL_SUBSCRIPTION_CABINET_HOURS_CAP', 48)),

    /** Максимум часов при выдаче из админки */
    'admin_hours_max' => max(48, (int) env('TRIAL_SUBSCRIPTION_ADMIN_MAX_HOURS', 8760)),

    /** Через сколько часов после окончания триала отправить follow-up письмо (один раз на аккаунт) */
    'followup_after_expiry_hours' => max(1, (int) env('TRIAL_SUBSCRIPTION_FOLLOWUP_AFTER_EXPIRY_HOURS', 24)),

    /** Бонусный триал в follow-up письме, часов */
    'followup_bonus_hours' => max(1, (int) env('TRIAL_SUBSCRIPTION_FOLLOWUP_BONUS_HOURS', 24)),
];

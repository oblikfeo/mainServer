<?php

return [
    /** Длительность пробного доступа на боевых узлах (XUI expiryTime), часов */
    'hours' => max(1, (int) env('TRIAL_SUBSCRIPTION_HOURS', 3)),

    'quota_gb' => max(1, (int) env('TRIAL_SUBSCRIPTION_QUOTA_GB', 5)),

    'devices' => max(0, (int) env('TRIAL_SUBSCRIPTION_DEVICES', 1)),

    /**
     * Blitz/HY2 принимает только целые дни; при коротком триале XUI истекает раньше.
     */
    'hy2_expiration_days' => max(1, (int) env('TRIAL_SUBSCRIPTION_HY2_DAYS', 1)),

    /** Верхняя граница часов при самовыдаче из ЛК (реферальные бонусы и т.п.) */
    'cabinet_hours_cap' => max(1, (int) env('TRIAL_SUBSCRIPTION_CABINET_HOURS_CAP', 48)),

    /** Максимум часов при выдаче из админки */
    'admin_hours_max' => max(48, (int) env('TRIAL_SUBSCRIPTION_ADMIN_MAX_HOURS', 8760)),
];

<?php

return [
    /** Длительность пробного доступа на боевых узлах (XUI expiryTime), часов */
    'hours' => max(1, (int) env('TRIAL_SUBSCRIPTION_HOURS', 8)),

    'quota_gb' => max(1, (int) env('TRIAL_SUBSCRIPTION_QUOTA_GB', 5)),

    'devices' => max(0, (int) env('TRIAL_SUBSCRIPTION_DEVICES', 1)),

    /** Верхняя граница часов при самовыдаче из ЛК (реферальные бонусы и т.п.) */
    'cabinet_hours_cap' => max(1, (int) env('TRIAL_SUBSCRIPTION_CABINET_HOURS_CAP', 48)),

    /** Максимум часов при выдаче из админки */
    'admin_hours_max' => max(48, (int) env('TRIAL_SUBSCRIPTION_ADMIN_MAX_HOURS', 8760)),

    /** Включить cron follow-up (отключить — false в .env) */
    'followup_enabled' => filter_var(env('TRIAL_SUBSCRIPTION_FOLLOWUP_ENABLED', true), FILTER_VALIDATE_BOOL),

    /** Через сколько часов после окончания триала отправить follow-up письмо (один раз на аккаунт) */
    'followup_after_expiry_hours' => max(1, (int) env('TRIAL_SUBSCRIPTION_FOLLOWUP_AFTER_EXPIRY_HOURS', 24)),

    /**
     * Follow-up только если триал закончился ПОСЛЕ этой даты (ISO 8601, UTC/app TZ).
     * Защита от массовой рассылки по исторической базе при первом включении.
     */
    'followup_eligible_trials_ending_after' => env('TRIAL_SUBSCRIPTION_FOLLOWUP_ELIGIBLE_AFTER', '2026-07-05 13:00:00'),

    /**
     * Не слать, если с момента окончания триала прошло больше N часов (окно для hourly cron).
     * 24 ч задержка + до 48 ч «догон» = письмо только тем, кто не купил в первые сутки после триала.
     */
    'followup_max_hours_after_expiry' => max(
        25,
        (int) env('TRIAL_SUBSCRIPTION_FOLLOWUP_MAX_HOURS_AFTER_EXPIRY', 72),
    ),

    /** Бонусный триал в follow-up письме, часов */
    'followup_bonus_hours' => max(1, (int) env('TRIAL_SUBSCRIPTION_FOLLOWUP_BONUS_HOURS', 24)),
];

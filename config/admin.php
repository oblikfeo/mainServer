<?php

return [
    'username' => (string) env('ADMIN_USERNAME', ''),
    'password' => (string) env('ADMIN_PASSWORD', ''),

    /** Отдельный вход для маркетолога: видит только раздел «Маркетинг». Пустой логин — вход выключен. */
    'marketing_username' => (string) env('MARKETING_ADMIN_USERNAME', ''),
    'marketing_password' => (string) env('MARKETING_ADMIN_PASSWORD', ''),
];

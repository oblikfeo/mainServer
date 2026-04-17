<?php

return [
    /**
     * 3x-ui панель тестовой связки (bundle3).
     * Пример: http://158.160.219.3:9052/<basePath>/
     */
    'panel_base' => rtrim((string) env('TEST_KEYS_PANEL_BASE', ''), '/').'/',
    /** Публичный origin panel /sub/{subId} (может отличаться от panel_base) */
    'sub_origin' => rtrim((string) env('TEST_KEYS_SUB_ORIGIN', ''), '/'),
    /** Хост для X-Forwarded-Host/X-Real-IP при запросе /sub */
    'pub_host' => (string) env('TEST_KEYS_PUB_HOST', ''),
    'panel_username' => (string) env('TEST_KEYS_PANEL_USER', ''),
    'panel_password' => (string) env('TEST_KEYS_PANEL_PASSWORD', ''),
    'inbound_id' => (int) env('TEST_KEYS_INBOUND_ID', 0),

    /** Публичный хост (IP/домен) для ссылки клиенту */
    'public_host' => (string) env('TEST_KEYS_PUBLIC_HOST', ''),
    'public_port' => (int) env('TEST_KEYS_PUBLIC_PORT', 443),

    /** Reality-параметры (должны совпадать с inbound в панели) */
    'reality_sni' => (string) env('TEST_KEYS_REALITY_SNI', ''),
    'reality_public_key' => (string) env('TEST_KEYS_REALITY_PBK', ''),
    'reality_short_id' => (string) env('TEST_KEYS_REALITY_SID', ''),

    /** Параметры клиента */
    'flow' => (string) env('TEST_KEYS_FLOW', 'xtls-rprx-vision'),
    'fingerprint' => (string) env('TEST_KEYS_FP', 'chrome'),
    'vless_display_name' => (string) env('TEST_KEYS_DISPLAY_NAME', 'Надежда — тестовый период'),

    /** Дефолты для выдачи */
    'default_hours' => (int) env('TEST_KEYS_DEFAULT_HOURS', 8),
    'default_limit_ip' => (int) env('TEST_KEYS_DEFAULT_LIMIT_IP', 1),
    'default_quota_gb' => (int) env('TEST_KEYS_DEFAULT_QUOTA_GB', 5),
];


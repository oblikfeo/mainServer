<?php

/**
 * Панели 3x-ui: создание клиентов и выдача подписки (как в avavpn-mvp).
 * Порядок в bundle_order — сначала FI, потом NL (как в эталонном скрипте).
 */
return [
    'panel_username' => env('XUI_PANEL_USER', ''),
    'panel_password' => env('XUI_PANEL_PASSWORD', ''),

    'bundle_order' => ['fi', 'nl'],

    'nodes' => [
        'fi' => [
            'panel_base' => rtrim((string) env('XUI_FI_BASE', ''), '/'),
            'sub_origin' => rtrim((string) env('XUI_FI_SUB_ORIGIN', ''), '/'),
            'pub_host' => env('XUI_FI_PUB_HOST', ''),
            'inbound_id' => (int) env('XUI_FI_INBOUND_ID', 1),
            'client_email_prefix' => env('XUI_FI_EMAIL_PREFIX', 'fi'),
            'vless_display_name' => env('XUI_FI_VLESS_NAME', 'Финляндия'),
        ],
        'nl' => [
            'panel_base' => rtrim((string) env('XUI_NL_BASE', ''), '/'),
            'sub_origin' => rtrim((string) env('XUI_NL_SUB_ORIGIN', ''), '/'),
            'pub_host' => env('XUI_NL_PUB_HOST', ''),
            'inbound_id' => (int) env('XUI_NL_INBOUND_ID', 1),
            'client_email_prefix' => env('XUI_NL_EMAIL_PREFIX', 'nl'),
            'vless_display_name' => env('XUI_NL_VLESS_NAME', 'Нидерланды'),
        ],
    ],

    /** Публичная ссылка подписки: {app_url}/sub/{token} */
    'sub_profile_update_hours' => env('SUB_PROFILE_UPDATE_HOURS', '12'),
    'sub_output_b64' => env('SUB_OUTPUT_B64', '0') === '1',
];

<?php

/**
 * Hysteria2 (Blitz): per-user управление через SSH + cli.py.
 *
 * Hy2 стоит отдельно от 3x-ui бандлов (fi/nl).
 * Юзеры создаются/удаляются через SSH: python3 core/cli.py add-user / remove-user.
 * Строка hy2:// собирается на хабе из сохранённых username + password + конфиг ниже.
 */
return [
    'enabled' => filter_var(env('HY2_ENABLED', false), FILTER_VALIDATE_BOOL),

    'host' => env('HY2_HOST', '222.167.208.75'),
    'port' => (int) env('HY2_PORT', 443),

    'obfs_type' => env('HY2_OBFS_TYPE', 'salamander'),
    'obfs_password' => env('HY2_OBFS_PASSWORD', ''),

    'pin_sha256' => env('HY2_PIN_SHA256', ''),
    'insecure' => filter_var(env('HY2_INSECURE', true), FILTER_VALIDATE_BOOL),

    'display_name' => env('HY2_DISPLAY_NAME', '🇭🇰 Высокая скорость'),
    'server_description' => env('HY2_SERVER_DESC', 'global'),
    'description_format' => strtolower(trim((string) env('HY2_DESC_FORMAT', 'b64'))),

    'ssh_host' => env('HY2_SSH_HOST', '222.167.208.75'),
    'ssh_user' => env('HY2_SSH_USER', 'root'),
    'ssh_key' => env('HY2_SSH_KEY', ''),
    'cli_path' => env('HY2_CLI_PATH', '/etc/hysteria/core/cli.py'),
    'venv_activate' => env('HY2_VENV_ACTIVATE', '/etc/hysteria/hysteria2_venv/bin/activate'),
];

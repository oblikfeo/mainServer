<?php

namespace App\Services\Subscription;

/**
 * Общие share-строки (п. 1–2 подписки): VLESS + Hy2 на домашнем сервере, одинаковые для всех клиентов.
 */
final class SubscriptionExtraShareLines
{
    /**
     * @return list<string>
     */
    public static function lines(): array
    {
        $extra = config('xui.sub_extra', []);
        if (! is_array($extra) || ! self::isConfigured($extra)) {
            return [];
        }

        $out = [];
        $fmt = (string) config('xui.vless_server_description_format', 'b64');

        $v = trim((string) ($extra['vless_uri'] ?? ''));
        if ($v !== '' && str_starts_with($v, 'vless://')) {
            $title = trim((string) ($extra['vless_title'] ?? ''));
            $sub = trim((string) ($extra['vless_subtitle'] ?? ''));
            if ($title !== '') {
                $v = VlessSubscriptionHelper::setVlessFragment($v, $title, $sub, $fmt);
            }
            $out[] = $v;
        }

        $h = self::ensureHy2Username(self::normalizeHy2Scheme(trim((string) ($extra['hy2_uri'] ?? ''))));
        if ($h !== '') {
            $frag = trim((string) ($extra['hy2_fragment'] ?? ''));
            if ($frag !== '') {
                $h = explode('#', $h, 2)[0].'#'.$frag;
            }
            $out[] = $h;
        }

        return $out;
    }

    /**
     * Полный порядок строк подписки для Happ: Домашний 1/2 → LTE (FI/NL) → Wi‑Fi (hy2 Blitz).
     *
     * @param  array{
     *     hy2_uri: ?string,
     *     vless_entries: list<array{line?: string}>
     * }  $bundle
     * @return list<string>
     */
    public static function orderedWithBundle(array $bundle, bool $includePanelVless = true): array
    {
        $lines = self::lines();

        if ($includePanelVless) {
            foreach ($bundle['vless_entries'] ?? [] as $entry) {
                $line = trim((string) ($entry['line'] ?? ''));
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        $hy2 = isset($bundle['hy2_uri']) ? trim((string) $bundle['hy2_uri']) : '';
        if ($hy2 !== '') {
            $lines[] = $hy2;
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public static function isConfigured(array $extra): bool
    {
        if (filter_var($extra['enabled'] ?? false, FILTER_VALIDATE_BOOL)) {
            return true;
        }

        return trim((string) ($extra['vless_uri'] ?? '')) !== ''
            || trim((string) ($extra['hy2_uri'] ?? '')) !== '';
    }

    /**
     * В подписке везде hy2://; клиентский ввод иногда hysteria2://.
     */
    public static function normalizeHy2Scheme(string $uri): string
    {
        if ($uri === '') {
            return '';
        }
        if (str_starts_with($uri, 'hysteria2://')) {
            return 'hy2://'.substr($uri, strlen('hysteria2://'));
        }

        return $uri;
    }

    /**
     * Happ/Xray не принимают hy2://:password@host без логина — подставляем SUB_EXTRA_HY2_USER.
     */
    public static function ensureHy2Username(string $uri): string
    {
        $uri = self::normalizeHy2Scheme($uri);
        if ($uri === '') {
            return '';
        }

        $parts = parse_url($uri);
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'hy2') {
            return $uri;
        }

        $user = (string) ($parts['user'] ?? '');
        $pass = (string) ($parts['pass'] ?? '');
        if ($user !== '' || $pass === '') {
            return $uri;
        }

        $authUser = trim((string) config('xui.sub_extra.hy2_auth_user', 'nadezhda'));
        if ($authUser === '') {
            return $uri;
        }

        $host = (string) ($parts['host'] ?? '');
        if ($host === '') {
            return $uri;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) && $parts['fragment'] !== '' ? '#'.$parts['fragment'] : '';

        return 'hy2://'
            .rawurlencode($authUser).':'.rawurlencode($pass)
            .'@'.$host.$port.$query.$fragment;
    }
}

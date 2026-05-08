<?php

namespace App\Services\Subscription;

/**
 * Дополнительные share-строки (vless/hy2) для всех подписок — из config('xui.sub_extra').
 */
final class SubscriptionExtraShareLines
{
    /**
     * @return list<string>
     */
    public static function lines(): array
    {
        $extra = config('xui.sub_extra', []);
        if (! is_array($extra) || ! filter_var($extra['enabled'] ?? true, FILTER_VALIDATE_BOOL)) {
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

        $h = self::normalizeHy2Scheme(trim((string) ($extra['hy2_uri'] ?? '')));
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
}

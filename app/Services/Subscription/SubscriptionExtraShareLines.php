<?php

namespace App\Services\Subscription;

/**
 * Общие share-строки: Litnets (доступы5), RUVDS (доступыRUVDS) и т.д.
 *
 * Перед FI/NL подставляются общие share-строки (hy2:// или vless://) — заголовки из .env.
 * Порядок: Litnets → RUVDS → панельные FI/NL.
 */
final class SubscriptionExtraShareLines
{
    /**
     * @return list<string>
     */
    public static function lines(): array
    {
        $out = [];
        $fmt = (string) config('xui.vless_server_description_format', 'b64');

        foreach (self::extraBlocks() as $extra) {
            $v = self::resolveShareUri($extra);
            if ($v === '') {
                continue;
            }

            $title = trim((string) ($extra['vless_title'] ?? ''));
            $sub = trim((string) ($extra['vless_subtitle'] ?? ''));
            if ($title !== '') {
                $v = VlessSubscriptionHelper::setShareFragment($v, $title, $sub, $fmt);
            }
            $out[] = $v;
        }

        return $out;
    }

    /**
     * Порядок: shared (Litnets, RUVDS) → LTE (FI/NL).
     *
     * @param  array{vless_entries: list<array{line?: string}>}  $bundle
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

        return $lines;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function extraBlocks(): array
    {
        $blocks = [];

        $home = config('xui.sub_extra', []);
        if (is_array($home) && self::isConfigured($home)) {
            $blocks[] = $home;
        }

        $ruvds = config('xui.sub_extra_ruvds', []);
        if (is_array($ruvds) && self::isConfigured($ruvds)) {
            $blocks[] = $ruvds;
        }

        return $blocks;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public static function isConfigured(array $extra): bool
    {
        if (filter_var($extra['enabled'] ?? false, FILTER_VALIDATE_BOOL)) {
            return true;
        }

        return self::resolveShareUri($extra) !== '';
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private static function resolveShareUri(array $extra): string
    {
        $hy2 = trim((string) ($extra['hy2_uri'] ?? ''));
        if ($hy2 !== '' && str_starts_with($hy2, 'hy2://')) {
            return $hy2;
        }

        $vless = trim((string) ($extra['vless_uri'] ?? ''));
        if ($vless !== '' && str_starts_with($vless, 'vless://')) {
            return $vless;
        }

        return '';
    }
}

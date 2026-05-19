<?php

namespace App\Services\Subscription;

/**
 * Общие share-строки: VLESS Reality на Litnets (доступы5).
 *
 * Hysteria2 снят (2026-05-19) — Litnets теперь только VLESS на TCP:443.
 * Перед FI/NL подставляется одна общая ссылка на всех — заголовок и подпись
 * берутся из SUB_EXTRA_VLESS_TITLE / SUB_EXTRA_VLESS_SUBTITLE.
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

        return $out;
    }

    /**
     * Порядок: Litnets VLESS → LTE (FI/NL).
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
     * @param  array<string, mixed>  $extra
     */
    public static function isConfigured(array $extra): bool
    {
        if (filter_var($extra['enabled'] ?? false, FILTER_VALIDATE_BOOL)) {
            return true;
        }

        return trim((string) ($extra['vless_uri'] ?? '')) !== '';
    }
}

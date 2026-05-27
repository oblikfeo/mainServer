<?php

namespace App\Services\Subscription;

/**
 * Общие share-строки: Litnets (доступы5), RUVDS (доступыRUVDS) и т.д.
 *
 * Порядок в подписке Happ (критично для iOS serverDescription):
 *   RUVDS vless → FI/NL → Litnets hy2 в конце.
 * Раньше hy2 шёл первым (19e4393) — на телефоне Happ показывал «VLESS»/«HYSTERIA» вместо подписей.
 *
 * @see ba73389 Подписка Happ: порядок узлов Домашние → LTE → Wi‑Fi
 */
final class SubscriptionExtraShareLines
{
    /**
     * Все share-строки без панельных FI/NL: vless-блоки, затем hy2.
     *
     * @return list<string>
     */
    public static function lines(): array
    {
        return [...self::vlessShareLines(), ...self::hy2ShareLines()];
    }

    /**
     * @param  array{vless_entries: list<array{line?: string}>}  $bundle
     * @return list<string>
     */
    public static function orderedWithBundle(array $bundle, bool $includePanelVless = true): array
    {
        $lines = self::vlessShareLines();

        if ($includePanelVless) {
            foreach ($bundle['vless_entries'] ?? [] as $entry) {
                $line = trim((string) ($entry['line'] ?? ''));
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        foreach (self::hy2ShareLines() as $hy2Line) {
            $lines[] = $hy2Line;
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private static function vlessShareLines(): array
    {
        $out = [];
        foreach (self::vlessExtraBlocks() as $extra) {
            $line = self::formatShareLine($extra, (string) self::resolveVlessUri($extra));
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private static function hy2ShareLines(): array
    {
        $out = [];
        foreach (self::hy2ExtraBlocks() as $extra) {
            $line = self::formatShareLine($extra, (string) self::resolveHy2Uri($extra));
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function vlessExtraBlocks(): array
    {
        $blocks = [];

        $ruvds = config('xui.sub_extra_ruvds', []);
        if (is_array($ruvds) && self::resolveVlessUri($ruvds) !== '') {
            $blocks[] = $ruvds;
        }

        $home = config('xui.sub_extra', []);
        if (is_array($home) && self::resolveVlessUri($home) !== '') {
            $blocks[] = $home;
        }

        return $blocks;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function hy2ExtraBlocks(): array
    {
        $home = config('xui.sub_extra', []);
        if (! is_array($home) || self::resolveHy2Uri($home) === '') {
            return [];
        }

        return [$home];
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private static function formatShareLine(array $extra, string $uri): string
    {
        $uri = trim($uri);
        if ($uri === '') {
            return '';
        }

        $fmt = (string) config('xui.vless_server_description_format', 'b64');
        $title = trim((string) ($extra['vless_title'] ?? ''));
        $sub = trim((string) ($extra['vless_subtitle'] ?? ''));
        if ($title === '') {
            return $uri;
        }

        return VlessSubscriptionHelper::setShareFragment($uri, $title, $sub, $fmt);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public static function isConfigured(array $extra): bool
    {
        if (filter_var($extra['enabled'] ?? false, FILTER_VALIDATE_BOOL)) {
            return true;
        }

        return self::resolveHy2Uri($extra) !== '' || self::resolveVlessUri($extra) !== '';
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private static function resolveHy2Uri(array $extra): string
    {
        $hy2 = trim((string) ($extra['hy2_uri'] ?? ''));
        if ($hy2 !== '' && str_starts_with($hy2, 'hy2://')) {
            return $hy2;
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private static function resolveVlessUri(array $extra): string
    {
        $vless = trim((string) ($extra['vless_uri'] ?? ''));
        if ($vless !== '' && str_starts_with($vless, 'vless://')) {
            return $vless;
        }

        return '';
    }
}

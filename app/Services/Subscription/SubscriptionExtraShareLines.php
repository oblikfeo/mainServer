<?php

namespace App\Services\Subscription;

/**
 * Общие share-строки: 777 (доступы777), RUVDS (доступыRUVDS), NL shared (доступы11, опционально).
 *
 * Перед FI подставляются общие vless:// — заголовки из .env.
 * Порядок: 777 → RUVDS → CDN (общая) → FI (панель, если bundle_order) → NL shared (если включён) или NL (панель).
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

        foreach (self::leadingExtraBlocks() as $extra) {
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
     * Порядок: shared (777, RUVDS) → FI (панель) → NL shared или NL (панель).
     *
     * @param  array{vless_entries: list<array{key?: string, line?: string}>}  $bundle
     * @return list<string>
     */
    public static function orderedWithBundle(array $bundle, bool $includePanelVless = true): array
    {
        $lines = self::lines();

        if ($includePanelVless) {
            foreach ($bundle['vless_entries'] ?? [] as $entry) {
                $key = (string) ($entry['key'] ?? '');
                if ($key === 'nl' && self::nlSharedConfigured()) {
                    continue;
                }

                $line = trim((string) ($entry['line'] ?? ''));
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        $nlShared = self::nlSharedLine();
        if ($nlShared !== '') {
            $lines[] = $nlShared;
        }

        return $lines;
    }

    public static function nlSharedConfigured(): bool
    {
        $nl = config('xui.sub_extra_nl', []);

        return is_array($nl) && self::isConfigured($nl);
    }

    /**
     * Узлы 3x-ui, с которых снимаем per-client VLESS для /sub/{token}.
     *
     * @return list<string>
     */
    public static function panelBundleOrder(): array
    {
        $order = config('xui.bundle_order', ['fi', 'nl']);
        if (! is_array($order)) {
            return ['fi', 'nl'];
        }

        if (! self::nlSharedConfigured()) {
            return array_values(array_map('strval', $order));
        }

        return array_values(array_filter(
            array_map('strval', $order),
            static fn (string $key): bool => $key !== 'nl',
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function leadingExtraBlocks(): array
    {
        $blocks = [];

        $node777 = config('xui.sub_extra_777', []);
        if (is_array($node777) && self::isConfigured($node777)) {
            $blocks[] = $node777;
        }

        $ruvds = config('xui.sub_extra_ruvds', []);
        if (is_array($ruvds) && self::isConfigured($ruvds)) {
            $blocks[] = $ruvds;
        }

        $cdn = config('xui.sub_extra_cdn', []);
        if (is_array($cdn) && self::isConfigured($cdn)) {
            $blocks[] = $cdn;
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

    private static function nlSharedLine(): string
    {
        if (! self::nlSharedConfigured()) {
            return '';
        }

        $extra = config('xui.sub_extra_nl', []);
        if (! is_array($extra)) {
            return '';
        }

        $v = self::resolveShareUri($extra);
        if ($v === '') {
            return '';
        }

        $fmt = (string) config('xui.vless_server_description_format', 'b64');
        $title = trim((string) ($extra['vless_title'] ?? ''));
        $sub = trim((string) ($extra['vless_subtitle'] ?? ''));

        if ($title !== '') {
            $v = VlessSubscriptionHelper::setShareFragment($v, $title, $sub, $fmt);
        }

        return $v;
    }
}

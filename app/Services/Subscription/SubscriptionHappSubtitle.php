<?php

namespace App\Services\Subscription;

/**
 * Тексты под именем узла в Happ и в meta.serverDescription JSON-подписки.
 * Длина подписи ограничивается {@see HappServerDescriptionLimiter} (по умолчанию 30 символов, дока Happ).
 *
 * Приоритет: SUB_GRAY_* / HY2_GRAY_SUBTITLE, затем описание ноды, затем общий fallback.
 */
final class SubscriptionHappSubtitle
{
    public static function forBundle(string $bundleKey): string
    {
        $bundleKey = strtolower(trim($bundleKey));
        /** @var array<string, mixed> */
        $map = config('xui.sub_gray_subtitles', []);
        $override = is_array($map) ? trim((string) ($map[$bundleKey] ?? '')) : '';
        if ($override !== '') {
            return $override;
        }

        $nodes = config('xui.nodes', []);
        $node = is_array($nodes[$bundleKey] ?? null) ? $nodes[$bundleKey] : [];

        return self::preferNonEmpty([
            trim((string) ($node['vless_server_description'] ?? '')),
            trim((string) config('xui.vless_server_description', '')),
        ]);
    }

    /** Trial / TestKey: SUB_GRAY_TRIAL или общий fallback. */
    public static function forTestKey(): string
    {
        /** @var array<string, mixed> */
        $map = config('xui.sub_gray_subtitles', []);
        $override = is_array($map) ? trim((string) ($map['trial'] ?? '')) : '';
        if ($override !== '') {
            return $override;
        }

        return trim((string) config('xui.vless_server_description', ''));
    }

    /** HY2: HY2_GRAY_SUBTITLE или HY2_SERVER_DESC. */
    public static function forHy2(): string
    {
        $o = trim((string) config('hy2.gray_subtitle', ''));
        if ($o !== '') {
            return $o;
        }

        return trim((string) config('hy2.server_description', ''));
    }

    /**
     * @param  list<string>  $candidates
     */
    private static function preferNonEmpty(array $candidates): string
    {
        foreach ($candidates as $c) {
            if ($c !== '') {
                return $c;
            }
        }

        return '';
    }
}

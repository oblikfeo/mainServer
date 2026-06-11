<?php

namespace App\Services\Subscription;

/**
 * Тексты под именем узла в Happ и в meta.serverDescription JSON-подписки.
 * Длина подписи ограничивается {@see HappServerDescriptionLimiter} (по умолчанию 30 символов, дока Happ).
 *
 * Приоритет: SUB_GRAY_*, затем описание ноды, затем общий fallback.
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

        // Только описание узла (XUI_FI_* / XUI_NL_*). Общий fallback не подмешиваем — иначе FI/NL
        // получают serverDescription в URI, а iOS Happ рисует base64 в строке «протокол».
        return trim((string) ($node['vless_server_description'] ?? ''));
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

    /** Litnets (home) VLESS: SUB_GRAY_HOME, иначе общий fallback. */
    public static function forHome(): string
    {
        /** @var array<string, mixed> */
        $map = config('xui.sub_gray_subtitles', []);
        $override = is_array($map) ? trim((string) ($map['home'] ?? '')) : '';
        if ($override !== '') {
            return $override;
        }

        return trim((string) config('xui.vless_server_description', ''));
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

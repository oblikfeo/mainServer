<?php

namespace App\Services\Subscription;

use App\Models\AppSetting;
use Throwable;

/**
 * База + админка для happ://routing: одинаковый merge во всех рендерерах подписки.
 */
final class HappRoutingMergedInput
{
    /**
     * @return list<string>
     */
    public static function mergedDirectSites(): array
    {
        $cfg = config('xui.happ_routing', []);
        $configSites = $cfg['direct_sites'] ?? [];
        if (! is_array($configSites)) {
            $configSites = [];
        }

        $parsed = HappRoutingRulesParser::parse(self::adminRoutingRulesRaw());

        return self::mergeUniqueTokens($configSites, $parsed['sites']);
    }

    /**
     * Сырые строки доп. правил из админки (IP/CIDR/geoip:).
     *
     * @return list<string>
     */
    public static function adminDirectIpTokens(): array
    {
        $parsed = HappRoutingRulesParser::parse(self::adminRoutingRulesRaw());

        return array_values(array_filter(array_map('trim', $parsed['ips']), fn (string $s): bool => $s !== ''));
    }

    /**
     * @param  list<string>|array<int|string, mixed>  $base
     * @param  list<string>  $extra
     * @return list<string>
     */
    public static function mergeUniqueTokens(array $base, array $extra): array
    {
        $seen = [];
        $out = [];
        foreach ([...$base, ...$extra] as $s) {
            $s = trim((string) $s);
            if ($s === '') {
                continue;
            }
            $k = strtolower($s);
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $out[] = $s;
        }

        return $out;
    }

    private static function adminRoutingRulesRaw(): string
    {
        try {
            return (string) (AppSetting::getValue('happ_routing_rules') ?? '');
        } catch (Throwable) {
            return '';
        }
    }
}

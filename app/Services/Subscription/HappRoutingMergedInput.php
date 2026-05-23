<?php

namespace App\Services\Subscription;

use App\Models\AppSetting;
use Throwable;

/**
 * База + админка для happ://routing: одинаковый merge во всех рендерерах подписки.
 *
 * Источники:
 * - Конфиг xui.happ_routing.direct_sites / direct_ip / block_sites / block_ip (env: HAPP_*).
 * - Доп. правила из админки (AppSetting::happ_routing_rules), разделяемые HappRoutingRulesParser на sites/ips.
 */
final class HappRoutingMergedInput
{
    /**
     * DirectSites: domain:, full:, keyword:, regexp:, geosite:* (последнее работает только при HAPP_GEOSITE_URL !== '').
     *
     * @return list<string>
     */
    public static function mergedDirectSites(): array
    {
        $parsed = HappRoutingRulesParser::parse(self::adminRoutingRulesRaw());

        $base = self::ruvdsSharedNodeEnabled()
            ? self::configList('direct_sites_push_only_when_ruvds')
            : self::configList('direct_sites');

        return self::mergeUniqueTokens($base, $parsed['sites']);
    }

    /**
     * ProxySites для Happ (uri-подписка): явно через VPN на LTE.
     *
     * @return list<string>
     */
    public static function mergedProxySites(): array
    {
        if (! self::ruvdsSharedNodeEnabled()) {
            return [];
        }

        return self::configList('proxy_sites_when_ruvds');
    }

    /**
     * DirectIp: CIDR, IPv4, geoip:* (последнее работает только при HAPP_GEOIP_URL !== '').
     *
     * @return list<string>
     */
    public static function mergedDirectIp(): array
    {
        $parsed = HappRoutingRulesParser::parse(self::adminRoutingRulesRaw());

        return self::mergeUniqueTokens(self::configList('direct_ip'), $parsed['ips']);
    }

    /**
     * BlockSites: только из конфига (админка не блокирует, чтобы не сломать клиентов случайным правилом).
     *
     * @return list<string>
     */
    public static function mergedBlockSites(): array
    {
        return self::mergeUniqueTokens(self::configList('block_sites'), []);
    }

    /**
     * BlockIp: только из конфига.
     *
     * @return list<string>
     */
    public static function mergedBlockIp(): array
    {
        return self::mergeUniqueTokens(self::configList('block_ip'), []);
    }

    /**
     * Сырые строки доп. правил из админки (IP/CIDR/geoip:).
     *
     * @deprecated используйте mergedDirectIp() — возвращает merged-список (конфиг + админка).
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

    /**
     * @return list<string>
     */
    private static function configList(string $key): array
    {
        $cfg = config('xui.happ_routing', []);
        if (! is_array($cfg)) {
            return [];
        }
        $val = $cfg[$key] ?? [];
        if (! is_array($val)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $val), fn (string $s): bool => trim($s) !== ''));
    }

    private static function adminRoutingRulesRaw(): string
    {
        try {
            return (string) (AppSetting::getValue('happ_routing_rules') ?? '');
        } catch (Throwable) {
            return '';
        }
    }

    private static function ruvdsSharedNodeEnabled(): bool
    {
        $ruvds = config('xui.sub_extra_ruvds', []);

        return is_array($ruvds) && SubscriptionExtraShareLines::isConfigured($ruvds);
    }

    public static function isRuvdsMobileProfile(): bool
    {
        return self::ruvdsSharedNodeEnabled();
    }
}

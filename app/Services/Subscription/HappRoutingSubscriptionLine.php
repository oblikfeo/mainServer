<?php

namespace App\Services\Subscription;

/**
 * Строка маршрутизации Happ для тела подписки / заголовка routing.
 *
 * Документация: https://www.happ.su/main/dev-docs/routing — `happ://routing/off` отключает маршрутизацию.
 * При отключённом HAPP_ROUTING_ENABLED по умолчанию в подписку уходит только `happ://routing/off` (без профилей и geo URL).
 *
 * Если HAPP_ROUTING_ENABLED=true — профиль onadd/add с DirectSites/DirectIp/BlockSites. Geoipurl/Geositeurl
 * подставляются из конфига (по умолчанию Loyalsoldier — самый популярный набор: geosite:category-ru,
 * geoip:ru, geosite:category-ads-all и т.д.). Пустая строка в .env → geosite:/geoip: токены отбрасываются.
 *
 * @see https://www.happ.su/main/dev-docs/routing
 * @see https://github.com/Loyalsoldier/v2ray-rules-dat
 */
final class HappRoutingSubscriptionLine
{
    public const ROUTING_OFF_DEEPLINK = 'happ://routing/off';

    /**
     * Строка для подписки: отключение маршрутизации, либо happ://routing/onadd/… с правилами.
     */
    public static function feedRoutingLine(): ?string
    {
        $cfg = config('xui.happ_routing', []);
        if (! is_array($cfg)) {
            return null;
        }

        if (! filter_var($cfg['enabled'] ?? false, FILTER_VALIDATE_BOOL)) {
            return filter_var($cfg['send_off_when_disabled'] ?? true, FILTER_VALIDATE_BOOL)
                ? self::ROUTING_OFF_DEEPLINK
                : null;
        }

        if (
            filter_var($cfg['routing_off_when_ruvds'] ?? true, FILTER_VALIDATE_BOOL)
            && HappRoutingMergedInput::isRuvdsMobileProfile()
        ) {
            return self::ROUTING_OFF_DEEPLINK;
        }

        $name = trim((string) ($cfg['profile_name'] ?? 'direct'));
        if ($name === '') {
            $name = 'direct';
        }

        $directSites = HappRoutingMergedInput::mergedDirectSites();
        $directIp = HappRoutingMergedInput::mergedDirectIp();
        $proxySites = HappRoutingMergedInput::mergedProxySites();
        $blockSites = HappRoutingMergedInput::mergedBlockSites();
        $blockIp = HappRoutingMergedInput::mergedBlockIp();

        $onAdd = filter_var($cfg['onadd'] ?? true, FILTER_VALIDATE_BOOL);

        return self::buildOnAddLine(
            profileName: $name,
            directSites: $directSites,
            useOnAdd: $onAdd,
            extraDirectIp: $directIp,
            geoipUrl: trim((string) ($cfg['geoip_url'] ?? '')),
            geositeUrl: trim((string) ($cfg['geosite_url'] ?? '')),
            blockSites: $blockSites,
            blockIp: $blockIp,
            proxySites: $proxySites,
            domainStrategy: HappRoutingMergedInput::isRuvdsMobileProfile() ? 'IPIfNonMatch' : 'AsIs',
        );
    }

    /**
     * Стандартные приватные сети + broadcast (как в примере Happ).
     *
     * @return list<string>
     */
    private static function defaultDirectIp(): array
    {
        return [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '169.254.0.0/16',
            '224.0.0.0/4',
            '255.255.255.255',
        ];
    }

    /**
     * @param  list<string>  $directSites
     * @return list<string>
     */
    public static function sitesForHappProfile(array $directSites, bool $allowGeosite): array
    {
        $out = [];
        foreach ($directSites as $s) {
            $s = trim((string) $s);
            if ($s === '') {
                continue;
            }
            if (! $allowGeosite && str_starts_with(strtolower($s), 'geosite:')) {
                continue;
            }
            $out[] = $s;
        }

        return $out;
    }

    /**
     * @param  list<string>  $extraDirectIp
     * @return list<string>
     */
    public static function extraDirectIpForHappProfile(array $extraDirectIp, bool $allowGeoip): array
    {
        $out = [];
        foreach ($extraDirectIp as $s) {
            $s = trim((string) $s);
            if ($s === '') {
                continue;
            }
            if (! $allowGeoip && str_starts_with(strtolower($s), 'geoip:')) {
                continue;
            }
            $out[] = $s;
        }

        return $out;
    }

    /**
     * Сборка happ://routing/onadd|add/<base64 json>.
     *
     * @param  list<string>  $directSites    DirectSites (geosite: оставляется только при geositeUrl !== '')
     * @param  list<string>  $extraDirectIp  CIDR/IPv4/geoip:* (geoip: оставляется только при geoipUrl !== '')
     * @param  list<string>  $blockSites     BlockSites (geosite: оставляется только при geositeUrl !== '')
     * @param  list<string>  $blockIp        BlockIp (geoip: оставляется только при geoipUrl !== '')
     * @param  list<string>  $proxySites     ProxySites — явно через VPN
     */
    public static function buildOnAddLine(
        string $profileName,
        array $directSites,
        bool $useOnAdd = true,
        array $extraDirectIp = [],
        string $geoipUrl = '',
        string $geositeUrl = '',
        array $blockSites = [],
        array $blockIp = [],
        array $proxySites = [],
        string $domainStrategy = 'AsIs',
    ): ?string {
        $allowGeosite = $geositeUrl !== '';
        $allowGeoip = $geoipUrl !== '';

        $directSites = self::sitesForHappProfile(
            array_values(array_filter(array_map('trim', $directSites), fn (string $s): bool => $s !== '')),
            $allowGeosite,
        );
        $extraDirectIp = self::extraDirectIpForHappProfile(
            array_values(array_filter(array_map('trim', $extraDirectIp), fn (string $s): bool => $s !== '')),
            $allowGeoip,
        );
        $blockSites = self::sitesForHappProfile(
            array_values(array_filter(array_map('trim', $blockSites), fn (string $s): bool => $s !== '')),
            $allowGeosite,
        );
        $blockIp = self::extraDirectIpForHappProfile(
            array_values(array_filter(array_map('trim', $blockIp), fn (string $s): bool => $s !== '')),
            $allowGeoip,
        );
        $proxySites = self::sitesForHappProfile(
            array_values(array_filter(array_map('trim', $proxySites), fn (string $s): bool => $s !== '')),
            $allowGeosite,
        );

        if (
            $directSites === []
            && $extraDirectIp === []
            && $blockSites === []
            && $blockIp === []
            && $proxySites === []
        ) {
            return null;
        }

        // Запросы DoH к Cloudflare (1.1.1.1) иначе уходят в GlobalProxy → если узел недоступен, DNS не поднимается и «нет интернета» вообще.
        $doHBootstrapIpv4 = ['1.1.1.1/32', '1.0.0.1/32'];
        $directIpMerged = array_merge($doHBootstrapIpv4, self::defaultDirectIp(), $extraDirectIp);
        $seenIp = [];
        $directIp = [];
        foreach ($directIpMerged as $ip) {
            $k = strtolower($ip);
            if (isset($seenIp[$k])) {
                continue;
            }
            $seenIp[$k] = true;
            $directIp[] = $ip;
        }

        // DomainStrategy AsIs — иначе при IPIfNonMatch после доменных правил включался матч по IP и трафик мог уйти в прокси.
        // LastUpdated — по доке Happ помогает принудительно обновить профиль при изменении подписки.
        // Geoipurl/Geositeurl: либо реальные URL на .dat (по умолчанию Loyalsoldier), либо пустые строки —
        // последнее блокирует автоподстановку дефолтных URL клиентом (см. dev-docs/routing).
        $profile = [
            'Name' => $profileName,
            'GlobalProxy' => 'true',
            'RemoteDNSType' => 'DoH',
            'RemoteDNSDomain' => 'https://cloudflare-dns.com/dns-query',
            'RemoteDNSIP' => '1.1.1.1',
            'DomesticDNSType' => 'DoH',
            'DomesticDNSDomain' => 'https://dns.google/dns-query',
            'DomesticDNSIP' => '8.8.8.8',
            'Geoipurl' => $geoipUrl,
            'Geositeurl' => $geositeUrl,
            'DnsHosts' => [
                'cloudflare-dns.com' => '1.1.1.1',
                'dns.google' => '8.8.8.8',
            ],
            'DirectSites' => $directSites,
            'DirectIp' => $directIp,
            'ProxySites' => $proxySites,
            'ProxyIp' => [],
            'BlockSites' => $blockSites,
            'BlockIp' => $blockIp,
            'DomainStrategy' => $domainStrategy,
            'FakeDNS' => 'false',
            'LastUpdated' => (string) time(),
        ];

        $json = json_encode($profile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return null;
        }

        $b64 = base64_encode($json);
        $scheme = $useOnAdd ? 'happ://routing/onadd/' : 'happ://routing/add/';

        return $scheme.$b64;
    }
}

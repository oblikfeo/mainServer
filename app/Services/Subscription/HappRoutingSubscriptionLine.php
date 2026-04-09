<?php

namespace App\Services\Subscription;

/**
 * Строка маршрутизации Happ для тела подписки / заголовка routing.
 *
 * @see https://www.happ.su/main/dev-docs/routing
 */
final class HappRoutingSubscriptionLine
{
    /**
     * Geo файлы нужны только если используются токены geoip:/geosite:.
     *
     * @param  list<string>  $directSites
     * @param  list<string>  $directIp
     */
    private static function needsGeoFiles(array $directSites, array $directIp): bool
    {
        foreach ($directSites as $s) {
            $s = strtolower(trim((string) $s));
            if (str_starts_with($s, 'geosite:')) {
                return true;
            }
        }
        foreach ($directIp as $s) {
            $s = strtolower(trim((string) $s));
            if (str_starts_with($s, 'geoip:')) {
                return true;
            }
        }

        return false;
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
     * @param  list<string>  $extraDirectIp  Доп. DirectIp (CIDR, geoip:, одиночный IPv4).
     */
    public static function buildOnAddLine(
        string $profileName,
        array $directSites,
        bool $useOnAdd = true,
        array $extraDirectIp = [],
    ): ?string {
        $directSites = array_values(array_filter(array_map('trim', $directSites), fn (string $s): bool => $s !== ''));
        $extraDirectIp = array_values(array_filter(array_map('trim', $extraDirectIp), fn (string $s): bool => $s !== ''));

        $directIpMerged = array_merge(self::defaultDirectIp(), $extraDirectIp);
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

        if ($directSites === [] && $extraDirectIp === []) {
            return null;
        }

        $needGeo = self::needsGeoFiles($directSites, $directIp);

        // DomainStrategy AsIs — иначе при IPIfNonMatch после доменных правил включался матч по IP и трафик мог уйти в прокси.
        // LastUpdated — по доке Happ помогает принудительно обновить профиль при изменении подписки.
        $profile = [
            'Name' => $profileName,
            'GlobalProxy' => 'true',
            'RemoteDNSType' => 'DoH',
            'RemoteDNSDomain' => 'https://cloudflare-dns.com/dns-query',
            'RemoteDNSIP' => '1.1.1.1',
            'DomesticDNSType' => 'DoH',
            'DomesticDNSDomain' => 'https://dns.google/dns-query',
            'DomesticDNSIP' => '8.8.8.8',
            'DnsHosts' => [
                'cloudflare-dns.com' => '1.1.1.1',
                'dns.google' => '8.8.8.8',
            ],
            'DirectSites' => $directSites,
            'DirectIp' => $directIp,
            'DomainStrategy' => 'AsIs',
            'FakeDNS' => 'false',
            'LastUpdated' => (string) time(),
        ];

        if ($needGeo) {
            $profile['Geoipurl'] = 'https://github.com/Loyalsoldier/v2ray-rules-dat/releases/latest/download/geoip.dat';
            $profile['Geositeurl'] = 'https://github.com/Loyalsoldier/v2ray-rules-dat/releases/latest/download/geosite.dat';
        }

        $json = json_encode($profile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return null;
        }

        $b64 = base64_encode($json);
        $scheme = $useOnAdd ? 'happ://routing/onadd/' : 'happ://routing/add/';

        return $scheme.$b64;
    }
}

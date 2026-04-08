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
     */
    public static function buildOnAddLine(
        string $profileName,
        array $directSites,
        bool $useOnAdd = true,
    ): ?string {
        $directSites = array_values(array_filter(array_map('trim', $directSites), fn (string $s): bool => $s !== ''));
        if ($directSites === []) {
            return null;
        }

        $profile = [
            'Name' => $profileName,
            'GlobalProxy' => 'true',
            'RemoteDNSType' => 'DoH',
            'RemoteDNSDomain' => 'https://cloudflare-dns.com/dns-query',
            'RemoteDNSIP' => '1.1.1.1',
            'DomesticDNSType' => 'DoH',
            'DomesticDNSDomain' => 'https://dns.google/dns-query',
            'DomesticDNSIP' => '8.8.8.8',
            'Geoipurl' => 'https://github.com/Loyalsoldier/v2ray-rules-dat/releases/latest/download/geoip.dat',
            'Geositeurl' => 'https://github.com/Loyalsoldier/v2ray-rules-dat/releases/latest/download/geosite.dat',
            'DnsHosts' => [
                'cloudflare-dns.com' => '1.1.1.1',
                'dns.google' => '8.8.8.8',
            ],
            'DirectSites' => $directSites,
            'DirectIp' => self::defaultDirectIp(),
            'DomainStrategy' => 'IPIfNonMatch',
            'FakeDNS' => 'false',
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

<?php

namespace App\Services\Subscription;

/**
 * Разбор многострочного ввода из админки: URL, домен, IP/CIDR, geosite/geoip, готовые префиксы Xray.
 */
final class HappRoutingRulesParser
{
    public const MAX_OUTPUT_ENTRIES = 150;

    /**
     * @return array{sites: list<string>, ips: list<string>}
     */
    public static function parse(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $sites = [];
        $ips = [];

        foreach (explode("\n", $raw) as $line) {
            if (count($sites) + count($ips) >= self::MAX_OUTPUT_ENTRIES) {
                break;
            }

            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $lower = strtolower($line);
            foreach (['domain:', 'full:', 'keyword:', 'regexp:', 'geosite:'] as $p) {
                if (str_starts_with($lower, $p)) {
                    $sites[] = $line;

                    continue 2;
                }
            }
            if (str_starts_with($lower, 'geoip:')) {
                $ips[] = $line;

                continue;
            }

            if (self::isProbablyUrl($line)) {
                $host = self::hostFromUrl($line);
                if ($host !== null && self::isSafeHost($host)) {
                    $sites[] = 'domain:'.$host;
                }

                continue;
            }

            // Частый кейс из админки: "yandex.ru/internet" без схемы.
            // Пробуем интерпретировать как URL с https:// и взять host.
            if (str_contains($line, '/') && str_contains($line, '.')) {
                $host = self::hostFromUrl('https://'.ltrim($line, '/'));
                if ($host !== null && self::isSafeHost($host)) {
                    $sites[] = 'domain:'.$host;
                }

                continue;
            }

            if (self::isIPv4OrCidr($line)) {
                $ips[] = $line;

                continue;
            }

            if (self::isPlainDomainOrHostname($line)) {
                $sites[] = 'domain:'.$line;

                continue;
            }
        }

        return [
            'sites' => self::uniqueList($sites),
            'ips' => self::uniqueList($ips),
        ];
    }

    private static function isProbablyUrl(string $s): bool
    {
        return str_starts_with(strtolower($s), 'http://')
            || str_starts_with(strtolower($s), 'https://');
    }

    private static function hostFromUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        return strtolower($host);
    }

    private static function isSafeHost(string $host): bool
    {
        if (strlen($host) > 253) {
            return false;
        }

        return (bool) preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/i', $host);
    }

    private static function isIPv4OrCidr(string $s): bool
    {
        if (preg_match('/^(\d{1,3}\.){3}\d{1,3}(\/\d{1,2})?$/', $s, $m)) {
            if (isset($m[0])) {
                $parts = explode('/', $s, 2);
                $ip = $parts[0];
                foreach (explode('.', $ip) as $oct) {
                    if ((int) $oct > 255) {
                        return false;
                    }
                }
                if (isset($parts[1])) {
                    $pfx = (int) $parts[1];

                    return $pfx >= 0 && $pfx <= 32;
                }

                return true;
            }
        }

        return false;
    }

    private static function isPlainDomainOrHostname(string $s): bool
    {
        if (strlen($s) > 253) {
            return false;
        }

        if (! str_contains($s, '.')) {
            return false;
        }

        return (bool) preg_match('/^[a-z0-9.-]+$/i', $s);
    }

    /**
     * @param  list<string>  $list
     * @return list<string>
     */
    private static function uniqueList(array $list): array
    {
        $seen = [];
        $out = [];
        foreach ($list as $item) {
            $k = strtolower($item);
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $out[] = $item;
        }

        return $out;
    }
}

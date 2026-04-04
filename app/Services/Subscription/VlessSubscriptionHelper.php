<?php

namespace App\Services\Subscription;

final class VlessSubscriptionHelper
{
    public static function decodeSubLine(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (str_starts_with($raw, 'vless://')) {
            return $raw;
        }

        try {
            $decoded = base64_decode($raw, true);
            if ($decoded !== false && $decoded !== '') {
                return trim($decoded);
            }
        } catch (\Throwable) {
        }

        return $raw;
    }

    public static function setVlessFragment(string $url, string $displayName): string
    {
        if ($url === '' || ! str_starts_with($url, 'vless://')) {
            return $url;
        }
        $base = explode('#', $url, 2)[0];

        return $base.'#'.$displayName;
    }

    /**
     * Тело подписки 3x-ui может быть многострочным (#meta + vless) или целиком в base64.
     */
    public static function extractVlessLineFromSubscriptionBody(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $blobs = [$raw];
        if (! str_contains($raw, "\n") && preg_match('/^[A-Za-z0-9+\/]+=*$/', $raw)) {
            $inner = base64_decode($raw, true);
            if ($inner !== false && trim($inner) !== '') {
                $blobs[] = trim($inner);
            }
        }

        foreach ($blobs as $blob) {
            foreach (preg_split("/\r\n|\n|\r/", $blob) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                $decoded = self::decodeSubLine($line);
                if ($decoded !== '' && str_starts_with($decoded, 'vless://')) {
                    return $decoded;
                }
                $scraped = self::scrapeVlessFromText($line);
                if ($scraped !== '') {
                    return $scraped;
                }
            }

            $decoded = self::decodeSubLine($blob);
            if ($decoded !== '' && str_starts_with($decoded, 'vless://')) {
                return $decoded;
            }
            $scraped = self::scrapeVlessFromText($blob);
            if ($scraped !== '') {
                return $scraped;
            }
        }

        return '';
    }

    private static function scrapeVlessFromText(string $text): string
    {
        if (preg_match('#vless://[^\s<>"\']+#i', $text, $m)) {
            return trim($m[0]);
        }

        return '';
    }
}

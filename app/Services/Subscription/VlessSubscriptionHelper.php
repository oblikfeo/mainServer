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
}

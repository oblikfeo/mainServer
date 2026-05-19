<?php

namespace App\Services\Subscription;

/**
 * Хранение bound_hwid_hashes: один слот на пару (IP, тип платформы).
 * Happ на iOS при добавлении подписки может прислать несколько разных X-Hwid подряд — без слияния
 * появляются дубликаты «iOS» с одного адреса.
 */
final class SubscriptionHwidBindingStore
{
    /**
     * @param  list<string>  $hashes
     * @param  array<string, array{type?: string, label?: string, ip?: string, seen_at?: string}>  $metaMap
     * @return array{0: list<string>, 1: array<string, array{type?: string, label?: string, ip?: string, seen_at?: string}>}
     */
    public static function dropSameIpAndType(array $hashes, array $metaMap, string $ip, string $type): array
    {
        if ($ip === '' || $ip === '—' || $type === '' || $type === 'Неизвестно') {
            return [$hashes, $metaMap];
        }

        $kept = [];
        foreach ($hashes as $hash) {
            $meta = $metaMap[$hash] ?? null;
            if (
                is_array($meta)
                && ($meta['ip'] ?? '') === $ip
                && ($meta['type'] ?? '') === $type
            ) {
                unset($metaMap[$hash]);

                continue;
            }
            $kept[] = $hash;
        }

        return [array_values($kept), $metaMap];
    }

    /**
     * @param  array<string, array{type?: string, label?: string, ip?: string, seen_at?: string}>  $metaMap
     */
    public static function isBetterLabel(string $candidate, string $existing): bool
    {
        $candidate = trim($candidate);
        $existing = trim($existing);
        if ($candidate === '') {
            return false;
        }
        if ($existing === '') {
            return true;
        }

        $generic = ['iOS', 'iPhone', 'iPad', 'Android', 'Windows', 'macOS', 'Linux', 'Устройство', 'Неизвестно'];
        $candidateGeneric = in_array($candidate, $generic, true);
        $existingGeneric = in_array($existing, $generic, true);

        if ($existingGeneric && ! $candidateGeneric) {
            return true;
        }

        return strlen($candidate) > strlen($existing);
    }
}

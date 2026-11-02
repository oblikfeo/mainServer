<?php

namespace App\Services\Subscription;

/**
 * Хранение bound_hwid_hashes: вспомогательные функции для подписей устройств.
 * Вытеснение «тот же IP + тот же тип» убрано 02.11.2026: одно устройство = один слот, никто никого не выбивает.
 */
final class SubscriptionHwidBindingStore
{
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

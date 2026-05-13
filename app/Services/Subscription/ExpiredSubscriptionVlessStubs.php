<?php

namespace App\Services\Subscription;

use App\Models\Subscription;

/**
 * Две минимальные VLESS-строки для истёкшей подписки (без запросов к панелям).
 *
 * @see config('xui.sub_expired_stub')
 */
final class ExpiredSubscriptionVlessStubs
{
    private const BASE = 'vless://%s@127.0.0.1:1?encryption=none&security=none&type=tcp&headerType=none';

    public static function shouldUse(Subscription $sub): bool
    {
        $cfg = config('xui.sub_expired_stub', []);
        if (! is_array($cfg) || ! filter_var($cfg['enabled'] ?? true, FILTER_VALIDATE_BOOL)) {
            return false;
        }

        return $sub->isExpired();
    }

    /**
     * @return list<string>
     */
    public static function lines(): array
    {
        $cfg = config('xui.sub_expired_stub', []);
        $fmt = (string) config('xui.vless_server_description_format', 'dual');

        $pairs = [
            [
                trim((string) ($cfg['line1_title'] ?? 'Подписка окончена')),
                trim((string) ($cfg['line1_subtitle'] ?? 'Действие вашей подписки окончено')),
            ],
            [
                trim((string) ($cfg['line2_title'] ?? 'Для продления нажмите на ⓘ')),
                trim((string) ($cfg['line2_subtitle'] ?? '')),
            ],
        ];

        $out = [];
        $n = 1;
        foreach ($pairs as [$title, $subtitle]) {
            if ($title === '') {
                $title = '—';
            }
            $base = sprintf(self::BASE, sprintf('00000000-0000-0000-0000-%012d', $n));
            $out[] = VlessSubscriptionHelper::setVlessFragment($base, $title, $subtitle, $fmt);
            $n++;
        }

        return $out;
    }
}

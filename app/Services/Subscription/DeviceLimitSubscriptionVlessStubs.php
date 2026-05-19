<?php

namespace App\Services\Subscription;

/**
 * VLESS-заглушки при превышении лимита устройств Happ (HTTP 200 вместо 403).
 * Happ iOS на голый 403 показывает «Сервер подписки вернул ошибку…».
 *
 * @see config('xui.sub_device_limit_stub')
 */
final class DeviceLimitSubscriptionVlessStubs
{
    private const BASE = 'vless://%s@127.0.0.1:1?encryption=none&security=none&type=tcp&headerType=none';

    /**
     * @return list<string>
     */
    public static function lines(): array
    {
        $cfg = config('xui.sub_device_limit_stub', []);
        $fmt = (string) config('xui.vless_server_description_format', 'dual');

        $pairs = [
            [
                trim((string) ($cfg['line1_title'] ?? 'Слишком много устройств')),
                trim((string) ($cfg['line1_subtitle'] ?? 'Лимит привязок исчерпан')),
            ],
            [
                trim((string) ($cfg['line2_title'] ?? 'Сброс в личном кабинете')),
                trim((string) ($cfg['line2_subtitle'] ?? 'Настройки → устройства → отвязать')),
            ],
        ];

        $out = [];
        $n = 1;
        foreach ($pairs as [$title, $subtitle]) {
            if ($title === '') {
                $title = '—';
            }
            $base = sprintf(self::BASE, sprintf('00000000-0000-0000-0001-%012d', $n));
            $out[] = VlessSubscriptionHelper::setVlessFragment($base, $title, $subtitle, $fmt);
            $n++;
        }

        return $out;
    }
}

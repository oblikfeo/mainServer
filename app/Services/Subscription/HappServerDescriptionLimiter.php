<?php

namespace App\Services\Subscription;

/**
 * Подпись под именем узла в Happ (URI #title?serverDescription=… и JSON meta.serverDescription).
 * По доке премиум-функционала Happ — до 30 символов; на экране клиент может сокращать с «…».
 *
 * @see https://docs.happ-proxy.com/ru/getting-started/premium-functionality
 */
final class HappServerDescriptionLimiter
{
    public static function maxLength(): int
    {
        return max(1, min(160, (int) config('xui.happ_server_description_max_chars', 30)));
    }

    public static function clamp(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $max = self::maxLength();

        if (function_exists('mb_strlen') && mb_strlen($text) > $max) {
            return mb_substr($text, 0, $max);
        }

        if (strlen($text) > $max) {
            return substr($text, 0, $max);
        }

        return $text;
    }
}

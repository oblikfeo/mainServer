<?php

namespace App\Services\Subscription;

/**
 * Happ Provider ID в ответе подписки: HTTP-заголовок и строка в теле.
 *
 * @see https://www.happ.su/main/ru/dev-docs/provider-id
 */
final class HappProviderIdSubscriptionExtras
{
    /**
     * @return array{body_prefix: string, headers: array<string, string>}
     */
    public static function forSubscriptionToken(?string $token): array
    {
        $id = self::resolveId($token);
        if ($id === '') {
            return ['body_prefix' => '', 'headers' => []];
        }

        return [
            'body_prefix' => '#providerid '.$id."\n",
            'headers' => ['providerid' => $id],
        ];
    }

    private static function resolveId(?string $token): string
    {
        $token = $token !== null ? trim($token) : '';
        /** @var array<string, mixed> $map */
        $map = config('xui.happ_provider_id_by_token', []);
        if ($token !== '' && is_array($map) && array_key_exists($token, $map)) {
            $fromMap = trim((string) $map[$token]);
            if ($fromMap !== '') {
                return $fromMap;
            }
        }

        return trim((string) config('xui.happ_provider_id', ''));
    }
}

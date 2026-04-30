<?php

namespace App\Services\Subscription;

/**
 * Поля управления приложением Happ (App management): support-url, profile-web-page-url, announce.
 *
 * @see https://www.happ.su/main/dev-docs/app-management
 */
final class HappSubscriptionAppManagementExtras
{
    private const ANNOUNCE_MAX_LEN = 200;

    /**
     * @return array{body_meta_suffix: string, headers: array<string, string>}
     */
    public static function forResponses(): array
    {
        $supportUrl = self::normalizedUrl(
            trim((string) (config('marketing.telegram_support_url') ?: config('marketing.telegram_url')))
        );
        $webUrl = self::normalizedUrl(trim((string) config('marketing.subscription_site_url')));
        if ($webUrl === '') {
            $webUrl = self::normalizedUrl(rtrim((string) config('app.url'), '/'));
        }

        $announce = trim((string) config('marketing.subscription_announce', ''));
        if ($announce !== '' && function_exists('mb_strlen') && mb_strlen($announce) > self::ANNOUNCE_MAX_LEN) {
            $announce = mb_substr($announce, 0, self::ANNOUNCE_MAX_LEN);
        } elseif ($announce !== '' && strlen($announce) > self::ANNOUNCE_MAX_LEN) {
            $announce = substr($announce, 0, self::ANNOUNCE_MAX_LEN);
        }

        $body = '';
        $headers = [];

        if ($supportUrl !== '') {
            $body .= "#support-url: {$supportUrl}\n";
            $headers['support-url'] = $supportUrl;
        }

        if ($webUrl !== '') {
            $body .= "#profile-web-page-url: {$webUrl}\n";
            $headers['profile-web-page-url'] = $webUrl;
        }

        if ($announce !== '') {
            $body .= '#announce: '.$announce."\n";
        }

        return [
            'body_meta_suffix' => $body,
            'headers' => $headers,
        ];
    }

    private static function normalizedUrl(string $url): string
    {
        $url = trim($url);

        return $url === '' ? '' : rtrim($url, '/');
    }
}

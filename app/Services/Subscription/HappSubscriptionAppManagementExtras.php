<?php

namespace App\Services\Subscription;

/**
 * Поля управления приложением Happ (App management): support-url, profile-web-page-url, announce, color-profile.
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

        $iconColor = self::happRgbaHexFromConfig();
        if ($iconColor !== null) {
            $profileJson = json_encode(
                ['profileWebPageIconColor' => $iconColor],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
            if ($profileJson !== false) {
                $body .= '#color-profile: '.$profileJson."\n";
                $headers['color-profile'] = $profileJson;
            }
        }

        return [
            'body_meta_suffix' => $body,
            'headers' => $headers,
        ];
    }

    /**
     * @return non-falsy-string|null #RRGGBBAA для Happ или null (не задано / отключено / невалидно)
     */
    private static function happRgbaHexFromConfig(): ?string
    {
        $raw = trim((string) config('marketing.subscription_profile_web_icon_color', ''));
        if ($raw === '') {
            return null;
        }

        return self::normalizeHappRgbaHex($raw);
    }

    private static function normalizeHappRgbaHex(string $value): ?string
    {
        if ($value === '' || $value[0] !== '#') {
            return null;
        }
        $hex = strtoupper(substr($value, 1));
        if (! preg_match('/^[0-9A-F]+$/', $hex)) {
            return null;
        }
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2].'FF';
        } elseif (strlen($hex) === 6) {
            $hex .= 'FF';
        } elseif (strlen($hex) !== 8) {
            return null;
        }

        return '#'.$hex;
    }

    private static function normalizedUrl(string $url): string
    {
        $url = trim($url);

        return $url === '' ? '' : rtrim($url, '/');
    }
}

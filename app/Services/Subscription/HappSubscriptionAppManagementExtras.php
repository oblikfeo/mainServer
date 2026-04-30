<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use App\Models\TestKey;

/**
 * Поля управления приложением Happ (App management): support-url, profile-web-page-url, announce, color-profile.
 * #announce при персонализации — только счётчик привязанных устройств (Happ баннер в одну строку).
 *
 * @see https://www.happ.su/main/dev-docs/app-management
 */
final class HappSubscriptionAppManagementExtras
{
    private const ANNOUNCE_MAX_LEN = 200;

    /**
     * @return array{body_meta_suffix: string, headers: array<string, string>}
     */
    public static function forResponses(Subscription|TestKey|null $context = null): array
    {
        $supportUrl = self::normalizedUrl(
            trim((string) (config('marketing.telegram_support_url') ?: config('marketing.telegram_url')))
        );
        $webUrl = self::normalizedUrl(trim((string) config('marketing.subscription_site_url')));
        if ($webUrl === '') {
            $webUrl = self::normalizedUrl(rtrim((string) config('app.url'), '/'));
        }

        $announce = self::composeAnnounce($context);

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

    private static function composeAnnounce(Subscription|TestKey|null $context): string
    {
        if (! (bool) config('marketing.subscription_announce_personalize', true) || $context === null) {
            return '';
        }

        $block = match (true) {
            $context instanceof Subscription => self::deviceAnnounceForSubscription($context),
            $context instanceof TestKey => self::deviceAnnounceForTestKey($context),
            default => '',
        };

        return self::truncateAnnounce($block);
    }

    private static function deviceAnnounceForSubscription(Subscription $sub): string
    {
        $used = self::hwidUsedCount($sub->bound_hwid_hashes);
        $max = max(1, (int) $sub->devices);

        return trim(strtr((string) config('marketing.subscription_announce_line_devices'), [
            '{used}' => (string) $used,
            '{max}' => (string) $max,
        ]));
    }

    private static function deviceAnnounceForTestKey(TestKey $key): string
    {
        $used = self::hwidUsedCount($key->bound_hwid_hashes);
        $limitIp = (int) ($key->limit_ip ?? 0);
        if ($limitIp < 1) {
            $limitIp = max(1, (int) config('test_keys.default_limit_ip', 1));
        }

        return trim(strtr((string) config('marketing.subscription_announce_line_devices'), [
            '{used}' => (string) $used,
            '{max}' => (string) $limitIp,
        ]));
    }

    private static function hwidUsedCount(mixed $hashes): int
    {
        if (! is_array($hashes)) {
            return 0;
        }
        $n = 0;
        foreach ($hashes as $h) {
            if ($h === null || $h === '') {
                continue;
            }
            $n++;
        }

        return $n;
    }

    private static function truncateAnnounce(string $announce): string
    {
        if ($announce === '') {
            return '';
        }

        if (function_exists('mb_strlen') && mb_strlen($announce) > self::ANNOUNCE_MAX_LEN) {
            return mb_substr($announce, 0, self::ANNOUNCE_MAX_LEN);
        }

        if (strlen($announce) > self::ANNOUNCE_MAX_LEN) {
            return substr($announce, 0, self::ANNOUNCE_MAX_LEN);
        }

        return $announce;
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

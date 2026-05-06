<?php

namespace App\Services\Subscription;

use App\Models\AppSetting;
use App\Models\Subscription;
use App\Models\TestKey;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Поля управления приложением Happ (App management): support-url, profile-web-page-url, announce, color-profile.
 *
 * Тело #announce собирается из частей сверху вниз:
 *   1) Фикс. «Привязанные устройства: used/max» (всегда).
 *   2) Фикс. «Дней до окончания подписки: N» (только если задана дата).
 *   3) Необязательная строка (subscription_announce_line_site, плейсхолдер {site}) — plain text; URL в ней не кликабелен в Happ.
 *   4) Произвольный текст из админки (AppSetting `marketing_announce_text`),
 *      поддерживает переносы строк и плейсхолдеры {used} / {max} / {days} / {brand} / {support} / {site}.
 *
 * Отдельно: #profile-web-page-url — основной синий вход на сайт/ЛК в Happ (всегда, если есть URL).
 * Дополнительно sub-info: «войти» (активная подписка) или «продлить» (истёк срок; опционально по трафику).
 *
 * Всё кодируется в base64 и отдаётся как `announce: base64:<…>` одновременно
 * и в HTTP-заголовке, и в теле подписки. На практике Happ рендерит \n внутри
 * base64-полезной нагрузки — этим мы пробуем многострочный анонс без Provider ID.
 *
 * @see https://www.happ.su/main/dev-docs/app-management
 */
final class HappSubscriptionAppManagementExtras
{
    /**
     * Ограничение длины полезной нагрузки #announce (после base64 Happ показывает текст).
     * С запасом под несколько строк и URL; при необходимости см. конфиг и админский блок.
     */
    private const ANNOUNCE_MAX_LEN = 400;

    private const BYTES_PER_GB = 1_073_741_824;

    /**
     * @param  ?int  $usageUploadBytes  Сумма upload по узлам (байты); null — проверка только по дате истечения.
     * @param  ?int  $usageDownloadBytes  Сумма download по узлам (байты).
     * @return array{body_meta_suffix: string, headers: array<string, string>}
     */
    public static function forResponses(
        Subscription|TestKey|null $context = null,
        ?int $usageUploadBytes = null,
        ?int $usageDownloadBytes = null,
    ): array {
        $supportUrl = self::normalizedUrl(
            trim((string) (config('marketing.telegram_support_url') ?: config('marketing.telegram_url')))
        );

        $needsRenewal = self::contextNeedsRenewal($context, $usageUploadBytes, $usageDownloadBytes);
        $webUrl = self::happCabinetOrPublicSiteUrl($context, $needsRenewal);

        $subInfoTier = self::happSubInfoTier($webUrl, $needsRenewal);

        $announce = self::composeAnnounce($context, $needsRenewal);

        $body = '';
        $headers = [];

        if ($supportUrl !== '') {
            $body .= "#support-url: {$supportUrl}\n";
            $headers['support-url'] = $supportUrl;
        }

        // Синий вход в Happ — всегда отдельно от sub-info (док.: profile-web-page-url).
        if ($webUrl !== '') {
            $body .= "#profile-web-page-url: {$webUrl}\n";
            $headers['profile-web-page-url'] = $webUrl;
        }

        if ($subInfoTier !== 'none') {
            self::appendHappSubInfoBlock($body, $headers, $webUrl, $subInfoTier);
        }

        if ($announce !== '') {
            // Всегда base64 — это единственный документированный способ передать произвольный
            // текст через `announce:` (в т.ч. с управляющими символами вроде \n).
            $announceField = 'base64:'.base64_encode($announce);
            $body .= '#announce: '.$announceField."\n";
            $headers['announce'] = $announceField;
        }

        if ($webUrl !== '') {
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
        }

        return [
            'body_meta_suffix' => $body,
            'headers' => $headers,
        ];
    }

    /** @return 'none'|'login'|'renew' */
    private static function happSubInfoTier(string $webUrl, bool $needsRenewal): string
    {
        if ($webUrl === '') {
            return 'none';
        }

        if ($needsRenewal && (bool) config('marketing.happ_renew_sub_info_when_exhausted', true)) {
            return 'renew';
        }

        $rawDisable = trim((string) config('marketing.subscription_happ_sub_info_text', ''));
        if ($rawDisable === '0') {
            return 'none';
        }

        return trim((string) config('marketing.subscription_happ_sub_info_button_text', '')) !== ''
            ? 'login'
            : 'none';
    }

    private static function appendHappSubInfoBlock(string &$body, array &$headers, string $webUrl, string $tier): void
    {
        if ($webUrl === '') {
            return;
        }

        if ($tier === 'renew') {
            $rawBtn = trim((string) config('marketing.subscription_happ_sub_info_button_renew_text', ''));
            if ($rawBtn === '') {
                return;
            }
            $buttonText = self::truncateUtf8($rawBtn, 25);

            $rawCaption = trim((string) config('marketing.subscription_happ_sub_info_renew_caption', ''));
            $infoText = ($rawCaption === '' || $rawCaption === '0')
                ? "\u{200B}"
                : self::truncateUtf8($rawCaption, 200);

            $rawColor = trim((string) config('marketing.subscription_happ_sub_info_renew_color', 'red'));
            if ($rawColor !== '' && $rawColor !== '0') {
                $body .= '#sub-info-color: '.$rawColor."\n";
                $headers['sub-info-color'] = $rawColor;
            }

            $body .= '#sub-info-text: '.$infoText."\n";
            $headers['sub-info-text'] = $infoText;

            $body .= '#sub-info-button-text: '.$buttonText."\n";
            $headers['sub-info-button-text'] = $buttonText;

            $body .= '#sub-info-button-link: '.$webUrl."\n";
            $headers['sub-info-button-link'] = $webUrl;

            return;
        }

        $rawBtn = trim((string) config('marketing.subscription_happ_sub_info_button_text', ''));
        if ($rawBtn === '') {
            return;
        }
        $buttonText = self::truncateUtf8($rawBtn, 25);

        $rawText = trim((string) config('marketing.subscription_happ_sub_info_text', ''));
        if ($rawText === '0') {
            return;
        }
        $infoText = ($rawText === '')
            ? "\u{200B}"
            : self::truncateUtf8($rawText, 200);

        $rawColor = trim((string) config('marketing.subscription_happ_sub_info_color', ''));
        if ($rawColor !== '' && $rawColor !== '0') {
            $body .= '#sub-info-color: '.$rawColor."\n";
            $headers['sub-info-color'] = $rawColor;
        }

        $body .= '#sub-info-text: '.$infoText."\n";
        $headers['sub-info-text'] = $infoText;

        $body .= '#sub-info-button-text: '.$buttonText."\n";
        $headers['sub-info-button-text'] = $buttonText;

        $body .= '#sub-info-button-link: '.$webUrl."\n";
        $headers['sub-info-button-link'] = $webUrl;
    }

    private static function composeAnnounce(Subscription|TestKey|null $context, bool $needsRenewal): string
    {
        if (! (bool) config('marketing.subscription_announce_personalize', true)) {
            return '';
        }

        if ($needsRenewal && (bool) config('marketing.subscription_announce_suppress_when_needs_renewal', true)) {
            $fallback = trim((string) config('marketing.subscription_happ_exhausted_announce_fallback', ''));

            return $fallback === '' ? '' : self::truncateAnnounce($fallback);
        }

        [$used, $max] = self::deviceCounters($context);
        $daysLeft = self::daysLeft($context);

        $vars = [
            '{used}' => (string) $used,
            '{max}' => (string) $max,
            '{days}' => $daysLeft !== null ? (string) $daysLeft : '',
            '{brand}' => (string) config('marketing.brand_name', 'Надежда'),
            '{support}' => (string) (config('marketing.telegram_support_url') ?: config('marketing.telegram_url')),
            '{site}' => self::happCabinetOrPublicSiteUrl($context, $needsRenewal),
        ];

        $lines = [];

        // Строка 1: устройства. Всегда.
        $devicesTpl = trim((string) config('marketing.subscription_announce_line_devices', 'Привязанные устройства: {used}/{max}'));
        if ($devicesTpl !== '') {
            $lines[] = strtr($devicesTpl, $vars);
        }

        // Строка 2: дней до окончания. Только при наличии даты.
        if ($daysLeft !== null) {
            $expiryTpl = trim((string) config('marketing.subscription_announce_line_expiry', 'Дней до окончания подписки: {days}'));
            if ($expiryTpl !== '') {
                $lines[] = strtr($expiryTpl, $vars);
            }
        }

        // Необязательная строка с {site} — только plain text (Happ не линкует URL внутри announce).
        $siteTpl = trim((string) config('marketing.subscription_announce_line_site', ''));
        if ($siteTpl !== '' && $vars['{site}'] !== '') {
            $lines[] = strtr($siteTpl, $vars);
        }

        // Произвольный блок из админки.
        $extra = self::adminAnnounceTemplate();
        if ($extra !== '') {
            $lines[] = strtr($extra, $vars);
        }

        $rendered = self::normalizeNewlines(implode("\n", $lines));
        $rendered = self::stripPlainTextBom($rendered);
        $rendered = trim($rendered, " \t\r\n");

        return self::truncateAnnounce($rendered);
    }

    private static function adminAnnounceTemplate(): string
    {
        try {
            $raw = AppSetting::getValue('marketing_announce_text') ?? '';
        } catch (Throwable) {
            $raw = '';
        }

        return trim(self::normalizeNewlines((string) $raw), " \t\n");
    }

    /**
     * Сколько целых дней осталось до окончания подписки. null — если даты нет; 0 — если уже истекла.
     */
    private static function daysLeft(Subscription|TestKey|null $context): ?int
    {
        $expiresAt = null;

        if ($context instanceof Subscription) {
            $expiresAt = $context->expiresAt();
        } elseif ($context instanceof TestKey) {
            $expiresAt = $context->expires_at instanceof Carbon ? $context->expires_at : null;
        }

        if (! $expiresAt instanceof Carbon) {
            return null;
        }

        $diffSeconds = $expiresAt->getTimestamp() - Carbon::now()->getTimestamp();
        if ($diffSeconds <= 0) {
            return 0;
        }

        // ceil: «осталось 23 часа» → 1 день, не 0; чувствительнее, чем floor, и согласуется с UX «N дней».
        return (int) ceil($diffSeconds / 86400);
    }

    /**
     * Унифицируем переносы строк к LF: textarea в браузерах посылает CRLF,
     * Happ ожидает (по эмпирике) именно \n внутри base64-payload.
     */
    private static function normalizeNewlines(string $value): string
    {
        return str_replace(["\r\n", "\r"], "\n", $value);
    }

    private static function stripPlainTextBom(string $value): string
    {
        if (strncmp($value, "\xEF\xBB\xBF", 3) === 0) {
            return substr($value, 3);
        }

        return $value;
    }

    /**
     * @return array{0:int,1:int} [used, max]
     */
    private static function deviceCounters(Subscription|TestKey|null $context): array
    {
        if ($context instanceof Subscription) {
            $used = self::hwidUsedCount($context->bound_hwid_hashes);
            $max = max(1, (int) $context->devices);

            return [$used, $max];
        }

        if ($context instanceof TestKey) {
            $used = self::hwidUsedCount($context->bound_hwid_hashes);
            $limit = (int) ($context->limit_ip ?? 0);
            if ($limit < 1) {
                $limit = max(1, (int) config('test_keys.default_limit_ip', 1));
            }

            return [$used, $limit];
        }

        return [0, 0];
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

    private static function truncateUtf8(string $value, int $maxChars): string
    {
        if ($maxChars < 1) {
            return '';
        }
        if (function_exists('mb_strlen')) {
            if (mb_strlen($value) > $maxChars) {
                return mb_substr($value, 0, $maxChars);
            }

            return $value;
        }

        return strlen($value) > $maxChars ? substr($value, 0, $maxChars) : $value;
    }

    private static function contextNeedsRenewal(
        Subscription|TestKey|null $context,
        ?int $usageUploadBytes,
        ?int $usageDownloadBytes,
    ): bool {
        if ($context instanceof Subscription) {
            if ($context->isExpired()) {
                return true;
            }

            if (! (bool) config('marketing.happ_renew_check_traffic', false)) {
                return false;
            }

            return self::subscriptionTrafficLooksExhausted($context, $usageUploadBytes, $usageDownloadBytes);
        }

        if ($context instanceof TestKey) {
            if ($context->isExpired()) {
                return true;
            }

            if (! (bool) config('marketing.happ_renew_check_traffic', false)) {
                return false;
            }

            return self::testKeyTrafficLooksExhausted($context, $usageUploadBytes, $usageDownloadBytes);
        }

        return false;
    }

    private static function subscriptionTrafficLooksExhausted(Subscription $sub, ?int $up, ?int $down): bool
    {
        if ($up === null || $down === null) {
            return false;
        }

        $quotaGb = (int) $sub->quota_gb;
        if ($quotaGb <= 0) {
            return false;
        }

        $cap = $quotaGb * self::BYTES_PER_GB;

        return ($up + $down) >= $cap;
    }

    private static function testKeyTrafficLooksExhausted(TestKey $key, ?int $up, ?int $down): bool
    {
        if ($up === null || $down === null) {
            return false;
        }

        $quotaGb = max(1, (int) $key->quota_gb);
        $cap = $quotaGb * self::BYTES_PER_GB;

        return ($up + $down) >= $cap;
    }

    private static function happCabinetOrPublicSiteUrl(Subscription|TestKey|null $context, bool $needsRenewal): string
    {
        $fallback = self::subscriptionWebUrl();
        if (! (bool) config('marketing.happ_cabinet_link_enabled', true)) {
            return $fallback;
        }

        if ($context instanceof Subscription) {
            $token = trim((string) $context->token);
            if ($token !== '' && (int) $context->user_id > 0) {
                $url = route('auth.via_token', ['token' => $token], absolute: true);

                return self::maybeAppendRenewIntent($url, $needsRenewal, true);
            }

            return $fallback;
        }

        if ($context instanceof TestKey) {
            $token = trim((string) $context->token);
            if ($token !== '' && (int) $context->user_id > 0 && ! $context->isRevoked() && ! $context->isExpired()) {
                $url = route('auth.via_token', ['token' => $token], absolute: true);

                return self::maybeAppendRenewIntent($url, $needsRenewal, true);
            }

            return $fallback;
        }

        return $fallback;
    }

    private static function maybeAppendRenewIntent(string $url, bool $needsRenewal, bool $isAuthViaTokenUrl): string
    {
        if (! $needsRenewal || ! $isAuthViaTokenUrl || $url === '') {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').'intent=renew';
    }

    /** Публичный URL сайта для анонса и кнопки профиля: MARKETING_SUBSCRIPTION_SITE_URL или APP_URL. */
    private static function subscriptionWebUrl(): string
    {
        $webUrl = self::normalizedUrl(trim((string) config('marketing.subscription_site_url')));
        if ($webUrl === '') {
            $webUrl = self::normalizedUrl(rtrim((string) config('app.url'), '/'));
        }

        return $webUrl;
    }
}

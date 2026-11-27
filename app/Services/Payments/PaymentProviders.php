<?php

namespace App\Services\Payments;

use App\Models\AppSetting;
use App\Services\Platega\PlategaClient;
use App\Services\Wata\WataH2hClient;

/**
 * Какая платёжка принимает оплату на сайте и в Telegram-боте: wata | platega.
 * Переключается в админке (/admin/payment-providers), хранится в app_settings.
 * Без записи в админке: сайт — Platega, бот — TELEGRAM_BOT_PAYMENT_PROVIDER (по умолчанию wata).
 */
final class PaymentProviders
{
    public const PROVIDERS = ['wata' => 'Wata', 'platega' => 'Platega'];

    public const SITE_KEY = 'payment_provider_site';

    public const BOT_KEY = 'payment_provider_bot';

    public static function site(): string
    {
        return self::normalize(AppSetting::getValue(self::SITE_KEY)) ?? 'platega';
    }

    public static function bot(): string
    {
        return self::normalize(AppSetting::getValue(self::BOT_KEY))
            ?? (config('payments.telegram_bot_provider') === 'platega' ? 'platega' : 'wata');
    }

    public static function set(string $key, string $provider): void
    {
        if (! in_array($key, [self::SITE_KEY, self::BOT_KEY], true) || self::normalize($provider) === null) {
            throw new \InvalidArgumentException('unknown provider setting');
        }
        AppSetting::setValue($key, $provider);
    }

    public static function isConfigured(string $provider): bool
    {
        return $provider === 'wata'
            ? trim((string) config('wata.access_token')) !== ''
            : app(PlategaClient::class)->isConfigured();
    }

    private static function normalize(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return array_key_exists($value, self::PROVIDERS) ? $value : null;
    }

    /**
     * Ссылка на оплату в Wata под уже сохранённый заказ (Wata сама показывает выбор СБП/карта).
     *
     * @return string URL страницы оплаты
     */
    public static function startWataLink(WataH2hClient $wata, \App\Models\PaymentOrder $order, string $successUrl, string $failUrl): string
    {
        $link = $wata->createPaymentLink([
            'type' => 'OneTime',
            'amount' => (float) number_format((int) $order->amount_rub, 2, '.', ''),
            'currency' => 'RUB',
            'description' => (string) $order->description,
            'orderId' => (string) $order->order_id,
            'successRedirectUrl' => $successUrl,
            'failRedirectUrl' => $failUrl,
        ]);

        $order->provider_link_id = $link['id'];
        $order->status = 'pending';
        $order->provider_payload = $link;
        $order->save();

        return $link['url'];
    }
}

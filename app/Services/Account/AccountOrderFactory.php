<?php

namespace App\Services\Account;

use App\Models\PaymentOrder;
use App\Models\Subscription;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Создаёт заказ новой схемы (purpose = account) из выбора на странице оплаты, в боте или на /buy.
 * Выбор задаётся так: срок подписки (или «не продлевать») и сколько всего устройств нужно.
 * Доп. устройства действуют до конца подписки.
 */
final class AccountOrderFactory
{
    /**
     * @param  int  $totalDevices  сколько устройств всего хочет аккаунт (базовые + доп.)
     * @param  int  $deviceMonths  не используется: доп. устройства всегда до конца подписки (параметр оставлен для старых клиентов API)
     * @return array<string, mixed> расчёт AccountPricing::quote
     *
     * @throws InvalidArgumentException
     */
    public static function quote(?Subscription $sub, ?string $termKey, int $totalDevices, int $deviceMonths = 0): array
    {
        if ($termKey === AccountPricing::PROMO_KEY) {
            return AccountPricing::promoQuote($sub); // число устройств задаёт сама акция
        }

        return AccountPricing::quote($sub, $termKey, $totalDevices);
    }

    /** Сколько доп. устройств уже оплачено и ещё не израсходовано. */
    public static function paidSlots(?Subscription $sub): int
    {
        if ($sub === null) {
            return 0;
        }

        return DeviceSlots::overview($sub)->filter(static fn (array $row): bool => $row['left_ms'] > 0)->count();
    }

    /**
     * @param  array<string, mixed>  $quote
     * @param  array<string, mixed>  $attributes  user_id / email / quick_buy_password / provider и т. п.
     */
    public static function create(array $quote, array $attributes): PaymentOrder
    {
        return PaymentOrder::query()->create(array_merge([
            'order_id' => 'ord_'.(string) Str::ulid(),
            'claim_token' => Str::random(48),
            'subscription_id' => null,
            'purpose' => 'account',
            'provider' => 'platega',
            'status' => 'created',
            'amount_rub' => (int) $quote['amount_rub'],
            'currency' => 'RUB',
            'description' => AccountPricing::describe($quote),
            'tariff_plan' => 'account',
            'tariff_period' => (string) ($quote['term'] ?? 'devices'),
            'days' => (int) $quote['days'],
            'devices' => (int) $quote['extra_devices'],
            'device_months' => (int) $quote['device_months'],
            'quota_gb' => 0,
        ], $attributes));
    }
}

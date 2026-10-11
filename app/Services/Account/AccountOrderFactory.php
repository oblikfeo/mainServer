<?php

namespace App\Services\Account;

use App\Models\PaymentOrder;
use App\Models\Subscription;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Создаёт заказ новой схемы (purpose = account) из выбора на странице оплаты, в боте или на /buy.
 * Выбор задаётся так: срок подписки (или «не продлевать»), сколько всего устройств нужно
 * и на сколько месяцев оплатить доп. устройства (0 — не оплачивать их сейчас).
 */
final class AccountOrderFactory
{
    /**
     * @param  int  $totalDevices  сколько устройств всего хочет аккаунт (базовые + доп.)
     * @param  int  $deviceMonths  на сколько месяцев оплатить доп. устройства; 0 — не оплачивать
     * @return array<string, mixed> расчёт AccountPricing::quote
     *
     * @throws InvalidArgumentException
     */
    public static function quote(?Subscription $sub, ?string $termKey, int $totalDevices, int $deviceMonths): array
    {
        $base = $sub !== null && (int) $sub->devices > 0 ? (int) $sub->devices : AccountPricing::baseDevices();
        if ($sub !== null && (int) $sub->devices <= 0) {
            $totalDevices = 0; // подписка без лимита устройств
            $base = 0;
        }
        $extraWanted = max(0, $totalDevices - $base);

        if ($deviceMonths < 1) {
            // Устройства сейчас не оплачиваются: нельзя просить больше, чем уже оплачено слотов.
            $have = $sub !== null ? DeviceSlots::overview($sub)->count() : 0;
            if ($extraWanted > $have) {
                throw new InvalidArgumentException('device_months_required');
            }
            $extraWanted = 0;
        }

        return AccountPricing::quote($sub, $termKey, $extraWanted, max(0, $deviceMonths));
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

<?php

namespace App\Services\Account;

use App\Models\Subscription;
use InvalidArgumentException;

/**
 * Цена покупки по новой схеме: срок подписки (со скидкой за срок) + доп. устройства
 * (ровно device_month_rub за месяц каждое, срок устройств выбирается отдельно).
 */
final class AccountPricing
{
    /** @return array<string, mixed> */
    public static function config(): array
    {
        $cfg = config('payments.account', []);

        return is_array($cfg) ? $cfg : [];
    }

    public static function baseDevices(): int
    {
        return max(1, (int) (self::config()['base_devices'] ?? 2));
    }

    public static function maxDevices(): int
    {
        return max(self::baseDevices(), (int) (self::config()['max_devices'] ?? 10));
    }

    public static function deviceMonthRub(): int
    {
        return max(1, (int) (self::config()['device_month_rub'] ?? 150));
    }

    public static function deviceMonthDays(): int
    {
        return max(1, (int) (self::config()['device_month_days'] ?? 30));
    }

    public static function deviceMaxMonths(): int
    {
        return max(1, (int) (self::config()['device_max_months'] ?? 6));
    }

    /**
     * @return array<string, array{key: string, label: string, months: int, days: int, amount_rub: int, per_month_rub: int, discount_percent: int}>
     */
    public static function terms(): array
    {
        $rows = self::config()['terms'] ?? [];
        $out = [];
        $monthPrice = 0;
        foreach (is_array($rows) ? $rows : [] as $key => $row) {
            if (! is_array($row)) {
                continue;
            }
            $months = max(1, (int) ($row['months'] ?? 1));
            $amount = (int) ($row['amount_rub'] ?? 0);
            $days = (int) ($row['days'] ?? 0);
            if ($amount < 1 || $days < 1) {
                continue;
            }
            if ($months === 1) {
                $monthPrice = $amount;
            }
            $out[(string) $key] = [
                'key' => (string) $key,
                'label' => (string) ($row['label'] ?? $key),
                'months' => $months,
                'days' => $days,
                'amount_rub' => $amount,
                'per_month_rub' => (int) round($amount / $months),
                // Подпись скидки можно задать в конфиге явно (круглое число для витрины).
                'discount_percent' => isset($row['discount_percent']) ? (int) $row['discount_percent'] : -1,
            ];
        }
        if ($monthPrice > 0) {
            foreach ($out as $key => $row) {
                if ($row['discount_percent'] >= 0) {
                    continue;
                }
                $full = $monthPrice * $row['months'];
                $out[$key]['discount_percent'] = $full > 0 ? max(0, (int) round(100 - $row['amount_rub'] * 100 / $full)) : 0;
            }
        }

        foreach ($out as $key => $row) {
            $out[$key]['discount_percent'] = max(0, $row['discount_percent']);
        }

        return $out;
    }

    /** Сколько доп. устройств можно иметь на этой подписке (с учётом её базовых). */
    public static function maxExtraDevices(?Subscription $sub): int
    {
        $base = $sub !== null ? (int) $sub->devices : self::baseDevices();
        if ($base <= 0) {
            return 0; // подписка без лимита устройств — докупать нечего
        }

        return max(0, self::maxDevices() - $base);
    }

    /**
     * @param  string|null  $termKey  ключ срока подписки или null — «не продлевать»
     * @param  int  $extraDevices  сколько доп. устройств оплачивается этим заказом
     * @param  int  $deviceMonths  на сколько месяцев оплачиваются эти устройства
     * @return array{term: string|null, term_label: string|null, days: int, base_rub: int, extra_devices: int, device_months: int, device_days: int, devices_rub: int, amount_rub: int}
     *
     * @throws InvalidArgumentException с кодом ошибки в сообщении
     */
    public static function quote(?Subscription $sub, ?string $termKey, int $extraDevices, int $deviceMonths): array
    {
        $term = null;
        if ($termKey !== null && $termKey !== '') {
            $term = self::terms()[$termKey] ?? null;
            if ($term === null) {
                throw new InvalidArgumentException('unknown_term');
            }
        }

        $active = $sub !== null && DeviceSlots::subscriptionActive($sub);
        if ($term !== null && $active && (int) $sub->expiry_ms <= 0) {
            throw new InvalidArgumentException('no_expiry'); // подписка без срока — продлевать нечего
        }
        if ($term === null && ! $active) {
            throw new InvalidArgumentException('term_required');
        }

        if ($extraDevices < 0 || $extraDevices > self::maxExtraDevices($sub)) {
            throw new InvalidArgumentException('devices_out_of_range');
        }

        if ($extraDevices === 0) {
            $deviceMonths = 0;
        } elseif ($deviceMonths < 1 || $deviceMonths > self::deviceMaxMonths()) {
            throw new InvalidArgumentException('device_months_out_of_range');
        }

        $baseRub = $term['amount_rub'] ?? 0;
        $devicesRub = $extraDevices * $deviceMonths * self::deviceMonthRub();
        $amount = $baseRub + $devicesRub;
        if ($amount < 1) {
            throw new InvalidArgumentException('nothing_to_pay');
        }

        return [
            'term' => $term['key'] ?? null,
            'term_label' => $term['label'] ?? null,
            'days' => $term['days'] ?? 0,
            'base_rub' => $baseRub,
            'extra_devices' => $extraDevices,
            'device_months' => $deviceMonths,
            'device_days' => $deviceMonths * self::deviceMonthDays(),
            'devices_rub' => $devicesRub,
            'amount_rub' => $amount,
        ];
    }

    /** Текст для описания заказа и чека. */
    public static function describe(array $quote): string
    {
        $parts = [];
        if (($quote['days'] ?? 0) > 0) {
            $parts[] = 'Подписка · '.$quote['term_label'];
        }
        if (($quote['extra_devices'] ?? 0) > 0) {
            $parts[] = 'Доп. устройства: '.$quote['extra_devices'].' × '.$quote['device_months'].' мес.';
        }

        return implode(' + ', $parts);
    }
}

<?php

namespace App\Services\Account;

use App\Models\ReferralGrant;
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

    /**
     * Базовые устройства подписки сверх стандартных (исторические лимиты: 3, 10, 30 устройств).
     * При продлении оплачиваются как доп. устройства на весь срок.
     */
    public static function overBaseDevices(?Subscription $sub): int
    {
        if ($sub === null) {
            return 0;
        }
        $over = (int) $sub->devices - self::baseDevices();
        if ($over <= 0) {
            return 0;
        }

        // Устройства, подаренные реферальной программой, остаются бесплатными.
        $gifted = $sub->user_id !== null
            ? ReferralGrant::query()
                ->where('beneficiary_user_id', $sub->user_id)
                ->where('kind', ReferralGrant::KIND_MILESTONE_EXTRA_DEVICE)
                ->count()
            : 0;

        return max(0, $over - $gifted);
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
        $overBase = $term !== null ? self::overBaseDevices($sub) : 0;
        $overBaseRub = $overBase * ($term['months'] ?? 0) * self::deviceMonthRub();
        $amount = $baseRub + $overBaseRub + $devicesRub;
        if ($amount < 1) {
            throw new InvalidArgumentException('nothing_to_pay');
        }

        return [
            'term' => $term['key'] ?? null,
            'term_label' => $term['label'] ?? null,
            'days' => $term['days'] ?? 0,
            'base_rub' => $baseRub,
            'over_base_devices' => $overBase,
            'over_base_rub' => $overBaseRub,
            'extra_devices' => $extraDevices,
            'device_months' => $deviceMonths,
            'device_days' => $deviceMonths * self::deviceMonthDays(),
            'devices_rub' => $devicesRub,
            'amount_rub' => $amount,
        ];
    }

    /** Ключ акции в выборе срока (term) — во всех точках оплаты: ЛК, /buy, бот. */
    public const PROMO_KEY = 'promo';

    /**
     * Акция «месяц + N устройств за фиксированную цену» или null, если выключена.
     *
     * @return array{key: string, label: string, months: int, days: int, devices: int, amount_rub: int, regular_rub: int, saving_rub: int}|null
     */
    public static function promo(): ?array
    {
        $p = self::config()['promo'] ?? null;
        if (! is_array($p) || ! ($p['enabled'] ?? false)) {
            return null;
        }
        $amount = (int) ($p['amount_rub'] ?? 0);
        $days = (int) ($p['days'] ?? 0);
        $devices = min(self::maxDevices(), (int) ($p['devices'] ?? 0));
        $months = max(1, (int) ($p['months'] ?? 1));
        if ($amount < 1 || $days < 1 || $devices <= self::baseDevices()) {
            return null;
        }
        // Обычная цена того же набора: подписка на месяц + доп. устройства на месяц.
        $monthTerm = collect(self::terms())->firstWhere('months', $months);
        $regular = (int) ($monthTerm['amount_rub'] ?? 0) + ($devices - self::baseDevices()) * $months * self::deviceMonthRub();

        return [
            'key' => self::PROMO_KEY,
            'label' => (string) ($p['label'] ?? ''),
            'months' => $months,
            'days' => $days,
            'devices' => $devices,
            'amount_rub' => $amount,
            'regular_rub' => $regular,
            'saving_rub' => max(0, $regular - $amount),
        ];
    }

    /** Можно ли этой подписке (null — новый аккаунт) купить акцию. */
    public static function promoAvailableFor(?Subscription $sub): bool
    {
        $promo = self::promo();
        if ($promo === null) {
            return false;
        }
        if ($sub === null) {
            return true;
        }
        $base = (int) $sub->devices;
        if ($base <= 0 || $base >= $promo['devices']) {
            return false; // без лимита устройств или и так больше — акция ничего не даёт
        }

        // Подписка без срока: продлевать нечего.
        return ! (DeviceSlots::subscriptionActive($sub) && (int) $sub->expiry_ms <= 0);
    }

    /**
     * Расчёт заказа по акции: срок на месяц + доп. устройства до promo.devices на тот же месяц.
     *
     * @throws InvalidArgumentException
     */
    public static function promoQuote(?Subscription $sub): array
    {
        $promo = self::promo();
        if ($promo === null || ! self::promoAvailableFor($sub)) {
            throw new InvalidArgumentException('promo_unavailable');
        }
        // Пробная подписка (1 устройство) при оплате становится обычной с базовыми устройствами.
        $base = max(self::baseDevices(), $sub !== null ? (int) $sub->devices : 0);
        $extra = max(0, $promo['devices'] - $base);

        return [
            'term' => self::PROMO_KEY,
            'term_label' => 'Акция · '.$promo['label'],
            'days' => $promo['days'],
            'base_rub' => $promo['amount_rub'],
            'over_base_devices' => 0,
            'over_base_rub' => 0,
            'extra_devices' => $extra,
            'device_months' => $promo['months'],
            'device_days' => $promo['days'],
            'devices_rub' => 0,
            'amount_rub' => $promo['amount_rub'],
        ];
    }

    /** Текст для описания заказа и чека. */
    public static function describe(array $quote): string
    {
        if (($quote['term'] ?? null) === self::PROMO_KEY) {
            return (string) $quote['term_label'];
        }
        $parts = [];
        if (($quote['days'] ?? 0) > 0) {
            $parts[] = 'Подписка · '.$quote['term_label']
                .(($quote['over_base_devices'] ?? 0) > 0 ? ' · '.(self::baseDevices() + (int) $quote['over_base_devices']).' устр.' : '');
        }
        if (($quote['extra_devices'] ?? 0) > 0) {
            $parts[] = 'Доп. устройства: '.$quote['extra_devices'].' × '.$quote['device_months'].' мес.';
        }

        return implode(' + ', $parts);
    }
}

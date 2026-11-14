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

    /**
     * Базовые устройства подписки для расчёта цены. Пробная подписка (1 устройство) при оплате
     * становится обычной, поэтому для неё база — стандартная. 0 — подписка без лимита устройств.
     */
    public static function effectiveBase(?Subscription $sub): int
    {
        if ($sub === null) {
            return self::baseDevices();
        }
        $devices = (int) $sub->devices;
        if ($devices <= 0) {
            return 0;
        }

        return $sub->is_trial ? max($devices, self::baseDevices()) : $devices;
    }

    /** Сколько доп. устройств можно иметь на этой подписке (с учётом её базовых). */
    public static function maxExtraDevices(?Subscription $sub): int
    {
        $base = self::effectiveBase($sub);
        if ($base <= 0) {
            return 0; // подписка без лимита устройств — докупать нечего
        }

        return max(0, self::maxDevices() - $base);
    }

    /** Оплаченная (не пробная) подписка со сроком, который ещё не кончился. */
    public static function isPaidActive(?Subscription $sub): bool
    {
        return $sub !== null && ! $sub->is_trial && (int) $sub->expiry_ms > 0 && DeviceSlots::subscriptionActive($sub);
    }

    /** Сколько дней осталось оплаченной подписке (неполный день считается целым); 0 — нет оплаченного срока. */
    public static function paidDaysLeft(?Subscription $sub): int
    {
        if (! self::isPaidActive($sub)) {
            return 0;
        }

        return max(1, (int) ceil(((int) $sub->expiry_ms - DeviceSlots::nowMs()) / DeviceSlots::DAY_MS));
    }

    /** Цена $count новых устройств до конца подписки ($days дней) — по device_month_rub за device_month_days, до рубля. */
    public static function restOfTermRub(int $count, int $days): int
    {
        if ($count < 1 || $days < 1) {
            return 0;
        }

        return (int) round($count * self::deviceMonthRub() * $days / self::deviceMonthDays());
    }

    /**
     * Расчёт заказа. Доп. устройство живёт столько же, сколько подписка:
     * — со сроком ($termKey): подписка продлевается, все доп. устройства оплачиваются на этот срок,
     *   а новые — ещё и за остаток текущей оплаченной подписки;
     * — без срока (null, только для оплаченной активной подписки): новые устройства до конца подписки.
     *
     * @param  int  $totalDevices  сколько устройств всего хочет аккаунт (базовые + доп.)
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException с кодом ошибки в сообщении
     */
    public static function quote(?Subscription $sub, ?string $termKey, int $totalDevices): array
    {
        $term = null;
        if ($termKey !== null && $termKey !== '') {
            $term = self::terms()[$termKey] ?? null;
            if ($term === null) {
                throw new InvalidArgumentException('unknown_term');
            }
        }

        $noExpiry = $sub !== null && DeviceSlots::subscriptionActive($sub) && (int) $sub->expiry_ms <= 0;
        if ($noExpiry) {
            throw new InvalidArgumentException('no_expiry'); // подписка без срока — ни продлевать, ни считать остаток
        }
        if ($term === null && ! self::isPaidActive($sub)) {
            throw new InvalidArgumentException('term_required');
        }

        $base = self::effectiveBase($sub);
        $extras = 0;
        $newDevices = 0;
        if ($base > 0) {
            $total = max($base, $totalDevices);
            if ($total > max($base, self::maxDevices())) {
                throw new InvalidArgumentException('devices_out_of_range');
            }
            $extras = $total - $base;
            $newDevices = max(0, $extras - AccountOrderFactory::paidSlots($sub));
        }

        $months = (int) ($term['months'] ?? 0);
        $restDays = self::paidDaysLeft($sub);
        $baseRub = (int) ($term['amount_rub'] ?? 0);
        $overBase = $term !== null ? self::overBaseDevices($sub) : 0;
        $overBaseRub = $overBase * $months * self::deviceMonthRub();
        $devicesRub = $term !== null ? $extras * $months * self::deviceMonthRub() : 0;
        $restRub = self::restOfTermRub($newDevices, $restDays);
        $amount = $baseRub + $overBaseRub + $devicesRub + $restRub;
        if ($term === null && $newDevices === 0) {
            throw new InvalidArgumentException('nothing_to_pay');
        }

        return [
            'term' => $term['key'] ?? null,
            'term_label' => $term['label'] ?? null,
            'months' => $months,
            'days' => (int) ($term['days'] ?? 0),
            'base_rub' => $baseRub,
            'over_base_devices' => $overBase,
            'over_base_rub' => $overBaseRub,
            'total_devices' => $base > 0 ? $base + $extras : 0,
            // Сколько доп. устройств заказ продлевает или создаёт (до конца нового срока подписки).
            'extra_devices' => $term !== null ? $extras : $newDevices,
            'new_devices' => $newDevices,
            'device_months' => 0, // 0 = устройства до конца подписки (см. AccountSubscription::applyPurchase)
            'device_days' => $restDays,
            'devices_rub' => $devicesRub,
            'rest_days' => $restDays,
            'rest_rub' => $restRub,
            'amount_rub' => max(1, $amount),
        ];
    }

    /**
     * Все варианты оплаты по числу устройств — так же, как их показывает страница «Оплата»
     * (акция подставляется вместо месяца, когда выбрано её число устройств). Для бота: цены считает сервер.
     *
     * @return list<array{devices: int, options: list<array{key: string, label: string, amount_rub: int, regular_rub: int}>}>
     */
    public static function offers(?Subscription $sub): array
    {
        $base = self::effectiveBase($sub);
        $counts = $base > 0 ? range($base, max($base, self::maxDevices())) : [0];
        $promo = self::promoAvailableFor($sub) ? self::promo() : null;
        $paidActive = self::isPaidActive($sub);
        $keys = array_keys(self::terms());
        if ($paidActive) {
            array_unshift($keys, null);
        }

        $out = [];
        foreach ($counts as $n) {
            $options = [];
            foreach ($keys as $key) {
                try {
                    $regular = (int) self::quote($sub, $key, $n)['amount_rub'];
                    $term = $key !== null ? self::terms()[$key] : null;
                    if ($promo !== null && $term !== null && $term['months'] === $promo['months'] && $n === $promo['devices']) {
                        $options[] = ['key' => self::PROMO_KEY, 'label' => 'Акция · '.$promo['label'], 'amount_rub' => (int) self::promoQuote($sub)['amount_rub'], 'regular_rub' => $regular];

                        continue;
                    }
                    $options[] = [
                        'key' => $key ?? 'none',
                        'label' => $key === null
                            ? 'До конца подписки ('.self::paidDaysLeft($sub).' дн.)'
                            : ($paidActive ? 'Продлить на '.$term['label'] : $term['label']),
                        'amount_rub' => $regular,
                        'regular_rub' => $regular,
                    ];
                } catch (InvalidArgumentException) {
                    continue; // например, «до конца подписки» без новых устройств — платить не за что
                }
            }
            $out[] = ['devices' => $n, 'options' => $options];
        }

        return $out;
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
        $base = self::effectiveBase($sub);
        if ($base <= 0 || $base >= $promo['devices']) {
            return false; // без лимита устройств или и так больше — акция ничего не даёт
        }

        // Подписка без срока: продлевать нечего.
        return ! (DeviceSlots::subscriptionActive($sub) && (int) $sub->expiry_ms <= 0);
    }

    /**
     * Расчёт заказа по акции: подписка +1 месяц и promo.devices устройств на весь срок подписки.
     * Цена акции заменяет «месяц + доп. устройства на месяц»; новые устройства на оплаченной активной
     * подписке, как и в обычном заказе, доплачиваются за остаток текущего срока.
     *
     * @throws InvalidArgumentException
     */
    public static function promoQuote(?Subscription $sub): array
    {
        $promo = self::promo();
        if ($promo === null || ! self::promoAvailableFor($sub)) {
            throw new InvalidArgumentException('promo_unavailable');
        }
        $base = self::effectiveBase($sub);
        $extra = max(0, $promo['devices'] - $base);
        $newDevices = max(0, $extra - AccountOrderFactory::paidSlots($sub));
        $restDays = self::paidDaysLeft($sub);
        $restRub = self::restOfTermRub($newDevices, $restDays);

        return [
            'term' => self::PROMO_KEY,
            'term_label' => 'Акция · '.$promo['label'],
            'months' => $promo['months'],
            'days' => $promo['days'],
            'base_rub' => $promo['amount_rub'],
            'over_base_devices' => 0,
            'over_base_rub' => 0,
            'total_devices' => $base + $extra,
            'extra_devices' => $extra,
            'new_devices' => $newDevices,
            'device_months' => 0, // устройства до конца подписки
            'device_days' => $restDays,
            'devices_rub' => 0,
            'rest_days' => $restDays,
            'rest_rub' => $restRub,
            'amount_rub' => $promo['amount_rub'] + $restRub,
        ];
    }

    /** Текст для описания заказа и чека. */
    public static function describe(array $quote): string
    {
        $parts = [];
        if (($quote['term'] ?? null) === self::PROMO_KEY) {
            $parts[] = (string) $quote['term_label'];
        } elseif (($quote['days'] ?? 0) > 0) {
            $parts[] = 'Подписка · '.$quote['term_label']
                .(($quote['over_base_devices'] ?? 0) > 0 ? ' · '.(self::baseDevices() + (int) $quote['over_base_devices']).' устр.' : '');
            if (($quote['extra_devices'] ?? 0) > 0) {
                $parts[] = 'доп. устройств: '.$quote['extra_devices'];
            }
        }
        $rest = (int) ($quote['rest_rub'] ?? 0);
        if (($quote['days'] ?? 0) > 0) {
            if ($rest > 0) {
                $parts[] = 'новые устройства за остаток срока ('.$quote['new_devices'].' × '.$quote['rest_days'].' дн.)';
            }
        } elseif (($quote['extra_devices'] ?? 0) > 0) {
            $parts[] = 'Доп. устройства: '.$quote['extra_devices'].' до конца подписки ('.$quote['rest_days'].' дн.)';
        }

        return implode(' + ', $parts);
    }
}

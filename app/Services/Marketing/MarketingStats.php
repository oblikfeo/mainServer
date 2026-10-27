<?php

namespace App\Services\Marketing;

use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Account\DeviceSlots;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Сводка для раздела «Маркетинг»: регистрации, оплаты, промокоды и источники за период.
 *
 * Регистрации и оплаты считаются по дням периода. Конверсия и выручка источника —
 * по когорте: люди, зарегистрированные в периоде, и все их оплаты (в том числе позже),
 * иначе продление через месяц выпадало бы из отчёта по каналу, который привёл человека.
 */
final class MarketingStats
{
    public const SOURCES = [
        'promo' => 'Промокод',
        'referral' => 'Реферальная ссылка',
        'campaign' => 'Реклама в боте',
        'bot' => 'Telegram-бот',
        'quick_buy' => 'Покупка без регистрации',
        'site' => 'Сайт',
    ];

    /**
     * @return array<string, mixed>
     */
    public function build(Carbon $from, Carbon $to): array
    {
        $tz = (string) config('app.timezone');
        $from = $from->copy()->timezone($tz)->startOfDay();
        $to = $to->copy()->timezone($tz)->endOfDay();
        $days = (int) $from->diffInDays($to) + 1;
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        $paidAll = PaymentOrder::query()->where('status', 'paid')->whereNotNull('paid_at')
            ->get(['id', 'user_id', 'amount_rub', 'paid_at', 'provider', 'purpose', 'days', 'devices', 'email', 'claim_token']);
        $firstPaidAt = $paidAll->groupBy('user_id')->map(fn (Collection $g) => $g->min('paid_at'));
        $quickBuyUsers = $paidAll->filter(fn ($o) => filled($o->email))->pluck('user_id')->unique()->flip();

        $users = User::query()->get(['id', 'email', 'created_at', 'registration_promo_code', 'referred_by', 'utm_campaign']);
        $subs = Subscription::query()->whereNotNull('user_id')->get(['id', 'user_id', 'expiry_ms', 'is_trial'])->keyBy('user_id');

        $inRange = fn ($at, Carbon $a, Carbon $b) => $at !== null && Carbon::parse($at)->betweenIncluded($a, $b);
        $periodUsers = $users->filter(fn ($u) => $inRange($u->created_at, $from, $to));
        $prevUsers = $users->filter(fn ($u) => $inRange($u->created_at, $prevFrom, $prevTo));
        $periodPaid = $paidAll->filter(fn ($o) => $inRange($o->paid_at, $from, $to));
        $prevPaid = $paidAll->filter(fn ($o) => $inRange($o->paid_at, $prevFrom, $prevTo));

        $isNewPayer = fn ($o) => $firstPaidAt->has($o->user_id) && (string) $firstPaidAt[$o->user_id] === (string) $o->paid_at;
        $paidByUser = $paidAll->groupBy('user_id');
        $source = fn ($u) => $this->sourceOf($u, $quickBuyUsers);

        return [
            'from' => $from,
            'to' => $to,
            'days' => $days,
            'kpi' => [
                'registrations' => $this->kpi($periodUsers->count(), $prevUsers->count()),
                'payments' => $this->kpi($periodPaid->count(), $prevPaid->count()),
                'revenue' => $this->kpi((int) $periodPaid->sum('amount_rub'), (int) $prevPaid->sum('amount_rub')),
                'new_payers' => $this->kpi($periodPaid->filter($isNewPayer)->count(), $prevPaid->filter($isNewPayer)->count()),
                'avg_check' => $this->kpi(
                    $periodPaid->count() > 0 ? (int) round($periodPaid->avg('amount_rub')) : 0,
                    $prevPaid->count() > 0 ? (int) round($prevPaid->avg('amount_rub')) : 0,
                ),
                'conversion' => $this->kpi(
                    $this->conversion($periodUsers, $paidByUser),
                    $this->conversion($prevUsers, $paidByUser),
                    isPercent: true,
                ),
            ],
            'daily' => $this->daily($from, $days, $tz, $periodUsers, $periodPaid, $isNewPayer),
            'promo' => $this->promoTable($users, $periodUsers, $paidByUser, $subs),
            'sources' => $this->sourcesTable($periodUsers, $paidByUser, $source),
            'campaigns' => $this->campaignsTable($periodUsers, $paidByUser),
            'payments' => [
                'new_revenue' => (int) $periodPaid->filter($isNewPayer)->sum('amount_rub'),
                'repeat_revenue' => (int) $periodPaid->reject($isNewPayer)->sum('amount_rub'),
                'by_channel' => $this->group($periodPaid, fn ($o) => $o->provider === 'wata' ? 'Telegram-бот' : (filled($o->email) ? 'Покупка без регистрации' : 'Сайт, кабинет')),
                'by_term' => $this->group($periodPaid, fn ($o) => match (true) {
                    (int) $o->days >= 170 => '6 месяцев',
                    (int) $o->days >= 85 => '3 месяца',
                    (int) $o->days >= 25 => '1 месяц',
                    default => 'Только устройства',
                }),
            ],
            'active_paid_now' => $subs->filter(fn ($s) => ! $s->is_trial && (int) $s->expiry_ms > 0 && DeviceSlots::subscriptionActive($s))->count(),
        ];
    }

    private function sourceOf(User $u, Collection $quickBuyUsers): string
    {
        return match (true) {
            filled($u->registration_promo_code) => 'promo',
            $u->referred_by !== null => 'referral',
            filled($u->utm_campaign) => 'campaign',
            str_ends_with((string) $u->email, '@telegram.nadezhda.local') => 'bot',
            $quickBuyUsers->has($u->id) => 'quick_buy',
            default => 'site',
        };
    }

    /** @return array{value: int|float, prev: int|float, delta: float|null, percent: bool} */
    private function kpi(int|float $value, int|float $prev, bool $isPercent = false): array
    {
        $delta = null;
        if ($isPercent) {
            $delta = round($value - $prev, 1);
        } elseif ($prev > 0) {
            $delta = round(($value - $prev) * 100 / $prev, 1);
        }

        return ['value' => $value, 'prev' => $prev, 'delta' => $delta, 'percent' => $isPercent];
    }

    private function conversion(Collection $cohort, Collection $paidByUser): float
    {
        if ($cohort->isEmpty()) {
            return 0.0;
        }

        return round($cohort->filter(fn ($u) => $paidByUser->has($u->id))->count() * 100 / $cohort->count(), 1);
    }

    /** @return list<array{date: string, label: string, registrations: int, promo: int, payments: int, revenue: int, new_payers: int}> */
    private function daily(Carbon $from, int $days, string $tz, Collection $users, Collection $paid, \Closure $isNewPayer): array
    {
        $rows = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i)->format('Y-m-d');
            $rows[$d] = ['date' => $d, 'label' => $from->copy()->addDays($i)->format('d.m'), 'registrations' => 0, 'promo' => 0, 'payments' => 0, 'revenue' => 0, 'new_payers' => 0];
        }
        foreach ($users as $u) {
            $d = Carbon::parse($u->created_at)->timezone($tz)->format('Y-m-d');
            if (isset($rows[$d])) {
                $rows[$d]['registrations']++;
                if (filled($u->registration_promo_code)) {
                    $rows[$d]['promo']++;
                }
            }
        }
        foreach ($paid as $o) {
            $d = Carbon::parse($o->paid_at)->timezone($tz)->format('Y-m-d');
            if (isset($rows[$d])) {
                $rows[$d]['payments']++;
                $rows[$d]['revenue'] += (int) $o->amount_rub;
                if ($isNewPayer($o)) {
                    $rows[$d]['new_payers']++;
                }
            }
        }

        return array_values($rows);
    }

    /** @return list<array<string, mixed>> */
    private function promoTable(Collection $users, Collection $periodUsers, Collection $paidByUser, Collection $subs): array
    {
        $defs = (array) config('promo.codes', []);
        $codes = collect(array_keys($defs))->map(fn ($c) => strtoupper((string) $c))
            ->merge($users->pluck('registration_promo_code')->filter()->map(fn ($c) => strtoupper((string) $c)))
            ->unique()->values();

        return $codes->map(function (string $code) use ($users, $periodUsers, $paidByUser, $subs, $defs) {
            $all = $users->filter(fn ($u) => strtoupper((string) $u->registration_promo_code) === $code);
            $payers = $all->filter(fn ($u) => $paidByUser->has($u->id));
            $def = $defs[$code] ?? null;
            $daysToPay = $payers->map(function ($u) use ($paidByUser) {
                return Carbon::parse($u->created_at)->diffInHours(Carbon::parse($paidByUser[$u->id]->min('paid_at'))) / 24;
            });

            return [
                'code' => $code,
                'active' => $def !== null,
                'bonus' => $def !== null ? ((int) ($def['trial_days'] ?? 0)).' дн. · '.((int) ($def['devices'] ?? config('trial_subscription.devices', 1))).' устр.' : 'код отключён',
                'registrations_period' => $periodUsers->filter(fn ($u) => strtoupper((string) $u->registration_promo_code) === $code)->count(),
                'registrations_all' => $all->count(),
                'payers' => $payers->count(),
                'conversion' => $all->count() > 0 ? round($payers->count() * 100 / $all->count(), 1) : null,
                'revenue' => (int) $payers->sum(fn ($u) => $paidByUser[$u->id]->sum('amount_rub')),
                'days_to_pay' => $daysToPay->isNotEmpty() ? round($daysToPay->avg(), 1) : null,
                'paying_now' => $all->filter(function ($u) use ($subs) {
                    $s = $subs[$u->id] ?? null;

                    return $s !== null && ! $s->is_trial && DeviceSlots::subscriptionActive($s);
                })->count(),
            ];
        })->sortByDesc('registrations_all')->values()->all();
    }

    /** @return list<array<string, mixed>> */
    private function sourcesTable(Collection $periodUsers, Collection $paidByUser, \Closure $source): array
    {
        $total = max(1, $periodUsers->count());
        $out = [];
        foreach (self::SOURCES as $key => $label) {
            $group = $periodUsers->filter(fn ($u) => $source($u) === $key);
            $payers = $group->filter(fn ($u) => $paidByUser->has($u->id));
            $out[] = [
                'key' => $key,
                'label' => $label,
                'registrations' => $group->count(),
                'share' => round($group->count() * 100 / $total, 1),
                'payers' => $payers->count(),
                'conversion' => $group->count() > 0 ? round($payers->count() * 100 / $group->count(), 1) : null,
                'revenue' => (int) $payers->sum(fn ($u) => $paidByUser[$u->id]->sum('amount_rub')),
            ];
        }
        usort($out, fn ($a, $b) => $b['registrations'] <=> $a['registrations']);

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private function campaignsTable(Collection $periodUsers, Collection $paidByUser): array
    {
        return $periodUsers->filter(fn ($u) => filled($u->utm_campaign))
            ->groupBy('utm_campaign')
            ->map(function (Collection $g, string $name) use ($paidByUser) {
                $payers = $g->filter(fn ($u) => $paidByUser->has($u->id));

                return [
                    'campaign' => $name,
                    'registrations' => $g->count(),
                    'payers' => $payers->count(),
                    'conversion' => round($payers->count() * 100 / $g->count(), 1),
                    'revenue' => (int) $payers->sum(fn ($u) => $paidByUser[$u->id]->sum('amount_rub')),
                ];
            })->sortByDesc('registrations')->values()->take(15)->all();
    }

    /** @return list<array{label: string, count: int, revenue: int}> */
    private function group(Collection $orders, \Closure $key): array
    {
        return $orders->groupBy($key)->map(fn (Collection $g, string $k) => [
            'label' => $k, 'count' => $g->count(), 'revenue' => (int) $g->sum('amount_rub'),
        ])->sortByDesc('revenue')->values()->all();
    }
}

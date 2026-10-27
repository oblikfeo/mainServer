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
        $subs = Subscription::query()->whereNotNull('user_id')->get(['id', 'user_id', 'expiry_ms', 'is_trial', 'bound_hwid_hashes'])->keyBy('user_id');

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
            'promo' => $this->promoReport($from, $days, $tz, $users, $periodUsers, $prevUsers, $paidByUser, $subs),
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

    /**
     * Промокоды: сводка за период, таблица и воронка по кодам, регистрации по дням, последние регистрации.
     *
     * «Подключили устройство» — у подписки есть привязанное устройство или человек уже платил.
     * Привязки неактивных аккаунтов очищены при переходе 12.10.2026, поэтому для старых
     * неоплативших это число занижено.
     *
     * @return array<string, mixed>
     */
    private function promoReport(Carbon $from, int $days, string $tz, Collection $users, Collection $periodUsers, Collection $prevUsers, Collection $paidByUser, Collection $subs): array
    {
        $defs = (array) config('promo.codes', []);
        $codeOf = fn ($u) => strtoupper((string) $u->registration_promo_code);
        $promoUsers = $users->filter(fn ($u) => filled($u->registration_promo_code));
        $codes = collect(array_keys($defs))->map(fn ($c) => strtoupper((string) $c))
            ->merge($promoUsers->map($codeOf))->unique()->values();

        $paidSum = fn ($u) => $paidByUser->has($u->id) ? (int) $paidByUser[$u->id]->sum('amount_rub') : 0;
        $activated = function ($u) use ($subs, $paidByUser) {
            $s = $subs[$u->id] ?? null;

            return $paidByUser->has($u->id) || ($s !== null && is_array($s->bound_hwid_hashes) && $s->bound_hwid_hashes !== []);
        };
        $payingNow = function ($u) use ($subs) {
            $s = $subs[$u->id] ?? null;

            return $s !== null && ! $s->is_trial && DeviceSlots::subscriptionActive($s);
        };
        $trialNow = function ($u) use ($subs) {
            $s = $subs[$u->id] ?? null;

            return $s !== null && $s->is_trial && DeviceSlots::subscriptionActive($s);
        };
        $trialDaysOf = fn (string $code) => (int) ($defs[$code]['trial_days'] ?? 0);

        $rows = $codes->map(function (string $code) use ($promoUsers, $periodUsers, $prevUsers, $paidByUser, $defs, $codeOf, $paidSum, $activated, $payingNow, $trialNow, $trialDaysOf) {
            $all = $promoUsers->filter(fn ($u) => $codeOf($u) === $code);
            $payers = $all->filter(fn ($u) => $paidByUser->has($u->id));
            $def = $defs[$code] ?? null;
            $revenue = (int) $payers->sum($paidSum);
            $orders = $payers->sum(fn ($u) => $paidByUser[$u->id]->count());
            $firstPay = $payers->map(fn ($u) => Carbon::parse($u->created_at)->diffInHours(Carbon::parse($paidByUser[$u->id]->min('paid_at'))) / 24);
            $trialDays = $trialDaysOf($code);
            $n = $all->count();

            return [
                'code' => $code,
                'active' => $def !== null,
                'bonus' => $def !== null ? $trialDays.' дн. · '.((int) ($def['devices'] ?? config('trial_subscription.devices', 1))).' устр.' : 'код отключён',
                'registrations_period' => $periodUsers->filter(fn ($u) => $codeOf($u) === $code)->count(),
                'registrations_prev' => $prevUsers->filter(fn ($u) => $codeOf($u) === $code)->count(),
                'registrations_all' => $n,
                'first_at' => $n > 0 ? Carbon::parse($all->min('created_at'))->timezone((string) config('app.timezone')) : null,
                'last_at' => $n > 0 ? Carbon::parse($all->max('created_at'))->timezone((string) config('app.timezone')) : null,
                'activated' => $all->filter($activated)->count(),
                'trial_now' => $all->filter($trialNow)->count(),
                'payers' => $payers->count(),
                'repeat_payers' => $payers->filter(fn ($u) => $paidByUser[$u->id]->count() > 1)->count(),
                'paying_now' => $all->filter($payingNow)->count(),
                'conversion' => $n > 0 ? round($payers->count() * 100 / $n, 1) : null,
                'activation' => $n > 0 ? round($all->filter($activated)->count() * 100 / $n, 1) : null,
                'retention' => $payers->count() > 0 ? round($all->filter($payingNow)->count() * 100 / $payers->count(), 1) : null,
                'revenue' => $revenue,
                'orders' => (int) $orders,
                'avg_check' => $orders > 0 ? (int) round($revenue / $orders) : null,
                'arpu' => $n > 0 ? (int) round($revenue / $n) : null,
                'ltv' => $payers->count() > 0 ? (int) round($revenue / $payers->count()) : null,
                'days_to_pay' => $firstPay->isNotEmpty() ? round($firstPay->avg(), 1) : null,
                'paid_in_trial' => $trialDays > 0 ? $firstPay->filter(fn ($d) => $d <= $trialDays)->count() : null,
            ];
        })->sortByDesc('registrations_all')->values();

        // Регистрации по дням: три самых крупных кода за период — отдельно, остальные — «Другие».
        $top = $rows->sortByDesc('registrations_period')->filter(fn ($r) => $r['registrations_period'] > 0)->take(3)->pluck('code')->values()->all();
        $daily = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i);
            $daily[$d->format('Y-m-d')] = ['label' => $d->format('d.m')] + array_fill_keys([...$top, 'Другие'], 0);
        }
        foreach ($periodUsers as $u) {
            if (! filled($u->registration_promo_code)) {
                continue;
            }
            $d = Carbon::parse($u->created_at)->timezone($tz)->format('Y-m-d');
            if (isset($daily[$d])) {
                $daily[$d][in_array($codeOf($u), $top, true) ? $codeOf($u) : 'Другие']++;
            }
        }
        $series = $top;
        if (collect($daily)->sum('Другие') > 0) {
            $series[] = 'Другие';
        }

        // Последние регистрации по промокодам за период (почта скрыта).
        $recent = $periodUsers->filter(fn ($u) => filled($u->registration_promo_code))
            ->sortByDesc('created_at')->take(25)
            ->map(function ($u) use ($codeOf, $paidSum, $activated, $payingNow, $trialNow, $paidByUser, $tz) {
                $state = match (true) {
                    $payingNow($u) => 'платит',
                    $trialNow($u) => 'пробный период',
                    $paidByUser->has($u->id) => 'платил, истекла',
                    default => 'не оплатил',
                };

                return [
                    'email' => $this->maskEmail((string) $u->email),
                    'code' => $codeOf($u),
                    'at' => Carbon::parse($u->created_at)->timezone($tz),
                    'state' => $state,
                    'activated' => $activated($u),
                    'paid' => $paidSum($u),
                ];
            })->values()->all();

        $periodPromo = $periodUsers->filter(fn ($u) => filled($u->registration_promo_code));
        $periodPromoPayers = $periodPromo->filter(fn ($u) => $paidByUser->has($u->id));

        return [
            'summary' => [
                'registrations' => $this->kpi($periodPromo->count(), $prevUsers->filter(fn ($u) => filled($u->registration_promo_code))->count()),
                'share' => $periodUsers->count() > 0 ? round($periodPromo->count() * 100 / $periodUsers->count(), 1) : 0.0,
                'activated' => $periodPromo->filter($activated)->count(),
                'payers' => $periodPromoPayers->count(),
                'conversion' => $periodPromo->count() > 0 ? round($periodPromoPayers->count() * 100 / $periodPromo->count(), 1) : null,
                'revenue' => (int) $periodPromoPayers->sum($paidSum),
                'trial_now' => $promoUsers->filter($trialNow)->count(),
                'paying_now' => $promoUsers->filter($payingNow)->count(),
            ],
            'codes' => $rows->all(),
            'daily' => array_values($daily),
            'series' => $series,
            'recent' => $recent,
        ];
    }

    private function maskEmail(string $email): string
    {
        if (str_ends_with($email, '@telegram.nadezhda.local')) {
            return 'Telegram-аккаунт';
        }
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $keep = mb_substr($local, 0, min(2, max(1, mb_strlen($local) - 1)));

        return $keep.str_repeat('•', max(2, min(6, mb_strlen($local) - mb_strlen($keep)))).($domain !== '' ? '@'.$domain : '');
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

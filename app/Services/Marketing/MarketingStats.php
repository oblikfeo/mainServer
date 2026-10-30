<?php

namespace App\Services\Marketing;

use App\Models\PaymentOrder;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Account\DeviceSlots;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Сводка для раздела «Маркетинг»: откуда приходят люди, промокоды, подключение и переход в оплату.
 *
 * Раздел видят люди вне компании, поэтому денежных сумм здесь нет вообще — только количество
 * людей и доли. Конверсия считается по когорте: люди, зарегистрированные в периоде,
 * и стали ли они платящими (в том числе позже конца периода).
 */
final class MarketingStats
{
    /** Каналы привлечения в фиксированном порядке (порядок задаёт и цвет на графике). */
    public const SOURCES = [
        'site' => 'Сайт',
        'bot' => 'Telegram-бот',
        'referral' => 'Реферальная ссылка',
        'promo' => 'Промокод',
        'campaign' => 'Реклама в боте',
        'quick_buy' => 'Покупка без регистрации',
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

        // Из оплат берём только факт и время первой оплаты человека — суммы не читаем.
        $paidAll = PaymentOrder::query()->where('status', 'paid')->whereNotNull('paid_at')->get(['user_id', 'paid_at', 'email']);
        $paidByUser = $paidAll->groupBy('user_id');
        $firstPaidAt = $paidByUser->map(fn (Collection $g) => Carbon::parse($g->min('paid_at')));
        $quickBuyUsers = $paidAll->filter(fn ($o) => filled($o->email))->pluck('user_id')->unique()->flip();

        $users = User::query()->get(['id', 'email', 'created_at', 'registration_promo_code', 'referred_by', 'utm_campaign']);
        $subs = Subscription::query()->whereNotNull('user_id')->get(['id', 'user_id', 'expiry_ms', 'is_trial', 'bound_hwid_hashes'])->keyBy('user_id');

        $inRange = fn ($at, Carbon $a, Carbon $b) => $at !== null && Carbon::parse($at)->betweenIncluded($a, $b);
        $periodUsers = $users->filter(fn ($u) => $inRange($u->created_at, $from, $to));
        $prevUsers = $users->filter(fn ($u) => $inRange($u->created_at, $prevFrom, $prevTo));

        $paid = fn ($u) => $paidByUser->has($u->id);
        $activated = function ($u) use ($subs, $paid) {
            $s = $subs[$u->id] ?? null;

            return $paid($u) || ($s !== null && is_array($s->bound_hwid_hashes) && $s->bound_hwid_hashes !== []);
        };
        $newPayersIn = fn (Carbon $a, Carbon $b) => $firstPaidAt->filter(fn (Carbon $at) => $at->betweenIncluded($a, $b))->count();
        $source = fn ($u) => $this->sourceOf($u, $quickBuyUsers);
        $isPromo = fn ($u) => filled($u->registration_promo_code);
        $share = fn (Collection $c, \Closure $f) => $c->isEmpty() ? 0.0 : round($c->filter($f)->count() * 100 / $c->count(), 1);

        return [
            'from' => $from,
            'to' => $to,
            'days' => $days,
            'kpi' => [
                'registrations' => $this->kpi($periodUsers->count(), $prevUsers->count()),
                'promo' => $this->kpi($periodUsers->filter($isPromo)->count(), $prevUsers->filter($isPromo)->count()),
                'referral' => $this->kpi($periodUsers->filter(fn ($u) => $u->referred_by !== null)->count(), $prevUsers->filter(fn ($u) => $u->referred_by !== null)->count()),
                'activation' => $this->kpi($share($periodUsers, $activated), $share($prevUsers, $activated), isPercent: true),
                'conversion' => $this->kpi($share($periodUsers, $paid), $share($prevUsers, $paid), isPercent: true),
                'new_payers' => $this->kpi($newPayersIn($from, $to), $newPayersIn($prevFrom, $prevTo)),
            ],
            'daily' => $this->daily($from, $days, $tz, $periodUsers, $firstPaidAt, $source),
            'promo' => $this->promoReport($from, $days, $tz, $users, $periodUsers, $prevUsers, $paidByUser, $firstPaidAt, $subs, $activated),
            'sources' => $this->sourcesTable($periodUsers, $prevUsers, $source, $activated, $paid),
            'campaigns' => $this->campaignsTable($periodUsers, $activated, $paid),
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

    /** @return list<array<string, mixed>> */
    private function daily(Carbon $from, int $days, string $tz, Collection $users, Collection $firstPaidAt, \Closure $source): array
    {
        $rows = [];
        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i);
            $rows[$d->format('Y-m-d')] = ['date' => $d->format('Y-m-d'), 'label' => $d->format('d.m'), 'registrations' => 0, 'new_payers' => 0]
                + array_fill_keys(array_keys(self::SOURCES), 0);
        }
        foreach ($users as $u) {
            $d = Carbon::parse($u->created_at)->timezone($tz)->format('Y-m-d');
            if (isset($rows[$d])) {
                $rows[$d]['registrations']++;
                // Канал «promo» = регистрации по промокоду (отдельного счётчика нет — ключи совпали бы).
                $rows[$d][$source($u)]++;
            }
        }
        foreach ($firstPaidAt as $at) {
            $d = $at->copy()->timezone($tz)->format('Y-m-d');
            if (isset($rows[$d])) {
                $rows[$d]['new_payers']++;
            }
        }

        return array_values($rows);
    }

    /**
     * Промокоды: сводка за период, воронка по кодам, регистрации по дням, последние регистрации.
     *
     * «Подключили устройство» — у подписки есть привязанное устройство или человек уже платил.
     * Привязки неактивных аккаунтов очищены при переходе 12.10.2026, поэтому для старых
     * неоплативших это число занижено.
     *
     * @return array<string, mixed>
     */
    private function promoReport(Carbon $from, int $days, string $tz, Collection $users, Collection $periodUsers, Collection $prevUsers, Collection $paidByUser, Collection $firstPaidAt, Collection $subs, \Closure $activated): array
    {
        $defs = (array) config('promo.codes', []);
        $codeOf = fn ($u) => strtoupper((string) $u->registration_promo_code);
        $promoUsers = $users->filter(fn ($u) => filled($u->registration_promo_code));
        $codes = collect(array_keys($defs))->map(fn ($c) => strtoupper((string) $c))
            ->merge($promoUsers->map($codeOf))->unique()->values();

        $payingNow = function ($u) use ($subs) {
            $s = $subs[$u->id] ?? null;

            return $s !== null && ! $s->is_trial && DeviceSlots::subscriptionActive($s);
        };
        $trialNow = function ($u) use ($subs) {
            $s = $subs[$u->id] ?? null;

            return $s !== null && $s->is_trial && DeviceSlots::subscriptionActive($s);
        };

        $rows = $codes->map(function (string $code) use ($promoUsers, $periodUsers, $prevUsers, $paidByUser, $firstPaidAt, $defs, $codeOf, $activated, $payingNow, $trialNow, $tz) {
            $all = $promoUsers->filter(fn ($u) => $codeOf($u) === $code);
            $payers = $all->filter(fn ($u) => $paidByUser->has($u->id));
            $def = $defs[$code] ?? null;
            $trialDays = (int) ($def['trial_days'] ?? 0);
            $firstPay = $payers->map(fn ($u) => Carbon::parse($u->created_at)->diffInHours($firstPaidAt[$u->id]) / 24);
            $n = $all->count();
            $nAct = $all->filter($activated)->count();
            $nNow = $all->filter($payingNow)->count();

            return [
                'code' => $code,
                'active' => $def !== null,
                'bonus' => $def !== null ? $trialDays.' дн. · '.((int) ($def['devices'] ?? config('trial_subscription.devices', 1))).' устр.'
                    .((int) ($def['first_payment_bonus_days'] ?? 0) > 0 ? ' · +'.(int) $def['first_payment_bonus_days'].' дн. к 1-й оплате' : '') : 'код отключён',
                'registrations_period' => $periodUsers->filter(fn ($u) => $codeOf($u) === $code)->count(),
                'registrations_prev' => $prevUsers->filter(fn ($u) => $codeOf($u) === $code)->count(),
                'registrations_all' => $n,
                'first_at' => $n > 0 ? Carbon::parse($all->min('created_at'))->timezone($tz) : null,
                'last_at' => $n > 0 ? Carbon::parse($all->max('created_at'))->timezone($tz) : null,
                'activated' => $nAct,
                'trial_now' => $all->filter($trialNow)->count(),
                'payers' => $payers->count(),
                'repeat_payers' => $payers->filter(fn ($u) => $paidByUser[$u->id]->count() > 1)->count(),
                'paying_now' => $nNow,
                'activation' => $n > 0 ? round($nAct * 100 / $n, 1) : null,
                'conversion' => $n > 0 ? round($payers->count() * 100 / $n, 1) : null,
                'conversion_activated' => $nAct > 0 ? round($payers->count() * 100 / $nAct, 1) : null,
                'retention' => $payers->count() > 0 ? round($nNow * 100 / $payers->count(), 1) : null,
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
            ->map(fn ($u) => [
                'email' => $this->maskEmail((string) $u->email),
                'code' => $codeOf($u),
                'at' => Carbon::parse($u->created_at)->timezone($tz),
                'state' => match (true) {
                    $payingNow($u) => 'платит',
                    $trialNow($u) => 'пробный период',
                    $paidByUser->has($u->id) => 'платил, истекла',
                    default => 'не оплатил',
                },
                'activated' => $activated($u),
            ])->values()->all();

        $periodPromo = $periodUsers->filter(fn ($u) => filled($u->registration_promo_code));

        return [
            'summary' => [
                'registrations' => $this->kpi($periodPromo->count(), $prevUsers->filter(fn ($u) => filled($u->registration_promo_code))->count()),
                'share' => $periodUsers->count() > 0 ? round($periodPromo->count() * 100 / $periodUsers->count(), 1) : 0.0,
                'activated' => $periodPromo->filter($activated)->count(),
                'payers' => $periodPromo->filter(fn ($u) => $paidByUser->has($u->id))->count(),
                'conversion' => $periodPromo->count() > 0 ? round($periodPromo->filter(fn ($u) => $paidByUser->has($u->id))->count() * 100 / $periodPromo->count(), 1) : null,
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
    private function sourcesTable(Collection $periodUsers, Collection $prevUsers, \Closure $source, \Closure $activated, \Closure $paid): array
    {
        $total = max(1, $periodUsers->count());
        $out = [];
        foreach (self::SOURCES as $key => $label) {
            $group = $periodUsers->filter(fn ($u) => $source($u) === $key);
            $n = $group->count();
            $out[] = [
                'key' => $key,
                'label' => $label,
                'registrations' => $n,
                'registrations_prev' => $prevUsers->filter(fn ($u) => $source($u) === $key)->count(),
                'share' => round($n * 100 / $total, 1),
                'activated' => $group->filter($activated)->count(),
                'payers' => $group->filter($paid)->count(),
                'activation' => $n > 0 ? round($group->filter($activated)->count() * 100 / $n, 1) : null,
                'conversion' => $n > 0 ? round($group->filter($paid)->count() * 100 / $n, 1) : null,
            ];
        }
        usort($out, fn ($a, $b) => $b['registrations'] <=> $a['registrations']);

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private function campaignsTable(Collection $periodUsers, \Closure $activated, \Closure $paid): array
    {
        return $periodUsers->filter(fn ($u) => filled($u->utm_campaign))
            ->groupBy('utm_campaign')
            ->map(fn (Collection $g, string $name) => [
                'campaign' => $name,
                'registrations' => $g->count(),
                'activated' => $g->filter($activated)->count(),
                'payers' => $g->filter($paid)->count(),
                'conversion' => round($g->filter($paid)->count() * 100 / $g->count(), 1),
            ])->sortByDesc('registrations')->values()->take(15)->all();
    }
}

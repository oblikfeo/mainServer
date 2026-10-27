@extends('layouts.admin')

@section('title', 'Маркетинг')

@php
    /** @var array<string, mixed> $data */
    $k = $data['kpi'];
    $fmt = static fn ($n) => number_format((float) $n, 0, ',', ' ');
    $pct = static fn ($n) => $n === null ? '—' : rtrim(rtrim(number_format((float) $n, 1, ',', ''), '0'), ',').'%';
    $delta = static function (array $kpi) {
        if ($kpi['delta'] === null) {
            return ['text' => 'нет данных за прошлый период', 'dir' => 'flat'];
        }
        $d = (float) $kpi['delta'];
        $dir = $d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat');
        $num = rtrim(rtrim(number_format(abs($d), 1, ',', ''), '0'), ',');
        $text = ($d > 0 ? '▲ +' : ($d < 0 ? '▼ −' : '■ ')).$num.($kpi['percent'] ? ' п.п.' : '%');

        return ['text' => $text.' к прошлому периоду', 'dir' => $dir];
    };
    $tiles = [
        ['key' => 'registrations', 'label' => 'Регистрации', 'value' => $fmt($k['registrations']['value'])],
        ['key' => 'payments', 'label' => 'Оплаты', 'value' => $fmt($k['payments']['value'])],
        ['key' => 'revenue', 'label' => 'Выручка', 'value' => $fmt($k['revenue']['value']).' ₽'],
        ['key' => 'new_payers', 'label' => 'Новые платящие', 'value' => $fmt($k['new_payers']['value'])],
        ['key' => 'conversion', 'label' => 'Конверсия в оплату', 'value' => $pct($k['conversion']['value']), 'hint' => 'доля зарегистрированных за период, кто оплатил'],
        ['key' => 'avg_check', 'label' => 'Средний чек', 'value' => $fmt($k['avg_check']['value']).' ₽'],
    ];
    $maxShare = max(1, collect($data['sources'])->max('registrations'));
    $payTotal = max(1, $data['payments']['new_revenue'] + $data['payments']['repeat_revenue']);
@endphp

@section('content')
<style>
    .mk { --ink: #0f172a; --ink-2: #475569; --muted: #94a3b8; --line: #e2e8f0; --card: #fff;
          --s1: #2a78d6; --s2: #eb6834; --s3: #6250d6; --up: #047857; --down: #b91c1c; }
    .mk * { box-sizing: border-box; }
    .mk-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
    .mk-title { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; color: var(--ink); }
    .mk-sub { margin-top: 0.25rem; font-size: 0.875rem; color: var(--ink-2); }
    .mk-filter { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
    .mk-seg { display: inline-flex; border: 1px solid var(--line); border-radius: 0.75rem; background: var(--card); overflow: hidden; }
    .mk-seg a { padding: 0.5rem 0.9rem; font-size: 0.875rem; font-weight: 600; color: var(--ink-2); text-decoration: none; }
    .mk-seg a + a { border-left: 1px solid var(--line); }
    .mk-seg a.is-on { background: var(--ink); color: #fff; }
    .mk-range { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.35rem 0.25rem 0.6rem; border: 1px solid var(--line); border-radius: 0.75rem; background: var(--card); font-size: 0.8125rem; color: var(--ink-2); }
    .mk-range input { border: 0; padding: 0.3rem; font-size: 0.8125rem; color: var(--ink); background: transparent; }
    .mk-range button { padding: 0.35rem 0.7rem; border-radius: 0.5rem; background: var(--ink); color: #fff; font-size: 0.8125rem; font-weight: 600; }

    .mk-kpis { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem; }
    @media (min-width: 900px) { .mk-kpis { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
    .mk-kpi { background: var(--card); border: 1px solid var(--line); border-radius: 1rem; padding: 1rem; min-width: 0; }
    .mk-kpi__label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ink-2); }
    .mk-kpi__value { margin-top: 0.4rem; font-size: 1.75rem; line-height: 1.1; font-weight: 800; color: var(--ink); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .mk-kpi__delta { margin-top: 0.4rem; font-size: 0.75rem; font-weight: 600; color: var(--ink-2); }
    .mk-kpi__delta.up { color: var(--up); } .mk-kpi__delta.down { color: var(--down); }
    .mk-kpi__hint { margin-top: 0.2rem; font-size: 0.6875rem; color: var(--muted); }

    .mk-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 1rem; }
    @media (min-width: 1100px) { .mk-grid--2 { grid-template-columns: 3fr 2fr; } }
    .mk-card { background: var(--card); border: 1px solid var(--line); border-radius: 1rem; padding: 1.1rem 1.1rem 1rem; min-width: 0; }
    .mk-card__head { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 0.5rem 1rem; margin-bottom: 0.75rem; }
    .mk-card__title { font-size: 1rem; font-weight: 800; color: var(--ink); }
    .mk-card__note { font-size: 0.75rem; color: var(--muted); }
    .mk-legend { display: flex; flex-wrap: wrap; gap: 0.9rem; font-size: 0.8125rem; color: var(--ink-2); }
    .mk-legend i { display: inline-block; width: 14px; height: 2px; vertical-align: middle; margin-right: 0.35rem; border-radius: 2px; }
    .mk-legend i.box { width: 10px; height: 10px; }

    .mk-chart { position: relative; width: 100%; }
    .mk-chart svg { display: block; width: 100%; overflow: visible; }
    .mk-chart .axis text { fill: var(--muted); font-size: 11px; font-variant-numeric: tabular-nums; }
    .mk-chart .grid line { stroke: var(--line); stroke-width: 1; }
    .mk-chart .end-label { font-size: 11px; font-weight: 700; fill: var(--ink-2); }
    .mk-tip { position: absolute; z-index: 5; pointer-events: none; min-width: 9rem; padding: 0.55rem 0.7rem; border-radius: 0.6rem; background: var(--ink); color: #fff; font-size: 0.8125rem; box-shadow: 0 8px 24px rgba(15, 23, 42, .18); opacity: 0; transform: translateY(4px); transition: opacity .12s, transform .12s; }
    .mk-tip.on { opacity: 1; transform: none; }
    .mk-tip .d { font-size: 0.75rem; color: #cbd5e1; margin-bottom: 0.3rem; }
    .mk-tip .r { display: flex; align-items: center; gap: 0.45rem; }
    .mk-tip .r b { font-variant-numeric: tabular-nums; }
    .mk-tip .r i { width: 12px; height: 2px; border-radius: 2px; }
    .mk-tip .r span { color: #cbd5e1; }

    .mk-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    .mk-table th { padding: 0.5rem 0.6rem; text-align: left; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink-2); border-bottom: 1px solid var(--line); white-space: nowrap; }
    .mk-table td { padding: 0.6rem; border-bottom: 1px solid #f1f5f9; color: var(--ink); vertical-align: middle; }
    .mk-table td.n, .mk-table th.n { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .mk-table tr:last-child td { border-bottom: 0; }
    .mk-scroll { overflow-x: auto; }
    .mk-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 700; }
    .mk-pill { display: inline-block; margin-left: 0.4rem; padding: 0.05rem 0.45rem; border-radius: 999px; font-size: 0.6875rem; font-weight: 700; background: #f1f5f9; color: var(--ink-2); }
    .mk-pill.on { background: #ecfdf5; color: var(--up); }
    .mk-bar { position: relative; height: 8px; border-radius: 4px; background: #f1f5f9; min-width: 80px; }
    .mk-bar span { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 4px; background: var(--s1); }
    .mk-split { display: flex; height: 12px; border-radius: 6px; overflow: hidden; gap: 2px; margin: 0.5rem 0 0.6rem; }
    .mk-split span { display: block; height: 100%; }
    .mk-empty { padding: 1.25rem 0; text-align: center; color: var(--muted); font-size: 0.875rem; }
    .mk { --c3: #1baf7a; }
    .mk-mini { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.6rem; margin-bottom: 1.25rem; }
    @media (min-width: 900px) { .mk-mini { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
    .mk-mini > div { display: flex; flex-direction: column; gap: 0.15rem; padding: 0.7rem 0.8rem; border-radius: 0.75rem; background: #f8fafc; min-width: 0; }
    .mk-mini span { font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink-2); }
    .mk-mini b { font-size: 1.25rem; font-weight: 800; color: var(--ink); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .mk-mini em { font-style: normal; font-size: 0.75rem; color: var(--ink-2); }
    .mk-mini em.up { color: var(--up); } .mk-mini em.down { color: var(--down); }
    .mk-sub-title { margin: 0.25rem 0 0.6rem; font-size: 0.8125rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink-2); }
    .mk-funnels { display: grid; grid-template-columns: 1fr; gap: 0.75rem; margin-bottom: 1.25rem; }
    @media (min-width: 900px) { .mk-funnels { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .mk-funnel { border: 1px solid var(--line); border-radius: 0.85rem; padding: 0.85rem; min-width: 0; }
    .mk-funnel__head { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; margin-bottom: 0.6rem; }
    .mk-funnel__head .mk-pill { margin-left: 0; }
    .mk-funnel__bonus { margin-left: auto; font-size: 0.75rem; color: var(--ink-2); }
    .mk-step { display: grid; grid-template-columns: 9.5rem 1fr 5.5rem; align-items: center; gap: 0.5rem; padding: 0.22rem 0; }
    .mk-step__label { font-size: 0.8125rem; color: var(--ink-2); }
    .mk-step__bar { height: 10px; border-radius: 5px; background: #f1f5f9; position: relative; }
    .mk-step__bar span { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 5px; background: var(--s1); }
    .mk-step__n { text-align: right; font-size: 0.8125rem; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .mk-step__n i { font-style: normal; color: var(--ink-2); margin-left: 0.25rem; }
    .mk-funnel__foot { margin-top: 0.5rem; font-size: 0.6875rem; color: var(--muted); }
    .mk-promo .mk-pill { white-space: nowrap; }
    .mk-pill.trial { background: #eff6ff; color: #1d4ed8; }
    .mk-pill.off { background: #fef2f2; color: var(--down); }
    .mk details summary { cursor: pointer; font-weight: 700; color: var(--ink-2); font-size: 0.875rem; }
</style>

<div class="mk">
    <div class="mk-head">
        <div>
            <div class="mk-title">Маркетинг</div>
            <div class="mk-sub">{{ $data['from']->format('d.m.Y') }} — {{ $data['to']->format('d.m.Y') }} · {{ $data['days'] }} дн. · сравнение с предыдущими {{ $data['days'] }} дн. · сейчас платящих подписок: <b>{{ $fmt($data['active_paid_now']) }}</b></div>
        </div>
        <form class="mk-filter" method="GET" action="{{ route('admin.marketing') }}">
            <div class="mk-seg" role="group" aria-label="Период">
                @foreach ($presets as $p)
                    <a href="{{ route('admin.marketing', ['days' => $p]) }}" class="{{ $preset === $p ? 'is-on' : '' }}">{{ $p }} дн.</a>
                @endforeach
            </div>
            <label class="mk-range">
                <span>с</span><input type="date" name="from" value="{{ $data['from']->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
                <span>по</span><input type="date" name="to" value="{{ $data['to']->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
                <button type="submit">Показать</button>
            </label>
        </form>
    </div>

    <div class="mk-kpis">
        @foreach ($tiles as $t)
            @php $d = $delta($k[$t['key']]); @endphp
            <div class="mk-kpi">
                <div class="mk-kpi__label">{{ $t['label'] }}</div>
                <div class="mk-kpi__value">{{ $t['value'] }}</div>
                <div class="mk-kpi__delta {{ $d['dir'] }}">{{ $d['text'] }}</div>
                @isset($t['hint'])<div class="mk-kpi__hint">{{ $t['hint'] }}</div>@endisset
            </div>
        @endforeach
    </div>

    <div class="mk-grid mk-grid--2">
        <div class="mk-card">
            <div class="mk-card__head">
                <div class="mk-card__title">Регистрации и оплаты по дням</div>
                <div class="mk-legend">
                    <span><i style="background:var(--s1)"></i>Регистрации</span>
                    <span><i style="background:var(--s2)"></i>Оплаты</span>
                </div>
            </div>
            <div class="mk-chart" id="mk-lines" style="height:260px" role="img" aria-label="Линейный график: регистрации и оплаты по дням"></div>
        </div>
        <div class="mk-card">
            <div class="mk-card__head">
                <div class="mk-card__title">Выручка по дням, ₽</div>
                <div class="mk-card__note">новые платящие: {{ $fmt($data['payments']['new_revenue']) }} ₽ · продления: {{ $fmt($data['payments']['repeat_revenue']) }} ₽</div>
            </div>
            <div class="mk-chart" id="mk-bars" style="height:260px" role="img" aria-label="Столбчатый график выручки по дням"></div>
        </div>
    </div>

    @php
        $pr = $data['promo'];
        $ps = $pr['summary'];
        $codeColors = ['var(--s1)', 'var(--s2)', 'var(--c3)'];
        $seriesColor = [];
        foreach ($pr['series'] as $i => $name) { $seriesColor[$name] = $name === 'Другие' ? 'var(--muted)' : ($codeColors[$i] ?? 'var(--muted)'); }
        $pd = $delta($ps['registrations']);
        $funnelMax = max(1, collect($pr['codes'])->max('registrations_all'));
        $stateClass = ['платит' => 'on', 'пробный период' => 'trial', 'платил, истекла' => 'off', 'не оплатил' => ''];
    @endphp
    <div class="mk-card mk-promo" style="margin-bottom:1rem">
        <div class="mk-card__head">
            <div class="mk-card__title">Промокоды</div>
            <div class="mk-card__note">регистрации на nadezhda.space/promo · оплаты и выручка — по всем, кто пришёл по коду</div>
        </div>

        <div class="mk-mini">
            <div><span>Регистрации за период</span><b>{{ $fmt($ps['registrations']['value']) }}</b><em class="{{ $pd['dir'] }}">{{ $pd['text'] }}</em></div>
            <div><span>Доля от всех регистраций</span><b>{{ $pct($ps['share']) }}</b></div>
            <div><span>Подключили устройство</span><b>{{ $fmt($ps['activated']) }}</b><em>из {{ $fmt($ps['registrations']['value']) }}</em></div>
            <div><span>Оплатили</span><b>{{ $fmt($ps['payers']) }}</b><em>конверсия {{ $pct($ps['conversion']) }}</em></div>
            <div><span>Выручка с них</span><b>{{ $fmt($ps['revenue']) }} ₽</b></div>
            <div><span>Сейчас</span><b>{{ $fmt($ps['trial_now']) }} · {{ $fmt($ps['paying_now']) }}</b><em>в пробном · платят</em></div>
        </div>

        @if (empty($pr['codes']))
            <div class="mk-empty">Промокодов пока нет</div>
        @else
            <div class="mk-sub-title">Воронка по кодам · за всё время</div>
            <div class="mk-funnels">
                @foreach ($pr['codes'] as $row)
                    @php
                        $steps = [
                            ['Зарегистрировались', $row['registrations_all'], null],
                            ['Подключили устройство', $row['activated'], $row['activation']],
                            ['Оплатили', $row['payers'], $row['conversion']],
                            ['Платят сейчас', $row['paying_now'], $row['retention']],
                        ];
                    @endphp
                    <div class="mk-funnel">
                        <div class="mk-funnel__head">
                            <span class="mk-code">{{ $row['code'] }}</span>
                            <span class="mk-pill {{ $row['active'] ? 'on' : '' }}">{{ $row['active'] ? 'активен' : 'выключен' }}</span>
                            <span class="mk-funnel__bonus">{{ $row['bonus'] }}</span>
                        </div>
                        @foreach ($steps as [$label, $n, $rate])
                            <div class="mk-step">
                                <div class="mk-step__label">{{ $label }}</div>
                                <div class="mk-step__bar"><span style="width: {{ max($n > 0 ? 2 : 0, $n * 100 / $funnelMax) }}%"></span></div>
                                <div class="mk-step__n"><b>{{ $fmt($n) }}</b>@if ($rate !== null) <i>{{ $pct($rate) }}</i>@endif</div>
                            </div>
                        @endforeach
                        <div class="mk-funnel__foot">
                            @if ($row['first_at'])
                                с {{ $row['first_at']->format('d.m.Y') }} · последняя регистрация {{ $row['last_at']->format('d.m.Y') }}
                            @else
                                регистраций ещё не было
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mk-sub-title">Деньги по кодам</div>
            <div class="mk-scroll">
                <table class="mk-table">
                    <thead><tr>
                        <th>Код</th><th class="n">Рег. за период</th><th class="n">прошлый период</th><th class="n">Рег. всего</th>
                        <th class="n">Оплатили</th><th class="n">Повторно</th><th class="n">Оплат</th><th class="n">Выручка</th>
                        <th class="n">На 1 регистрацию</th><th class="n">На 1 платящего</th><th class="n">Средний чек</th>
                        <th class="n">До 1-й оплаты</th><th class="n">Оплатили в пробный</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($pr['codes'] as $row)
                            <tr>
                                <td class="mk-code">{{ $row['code'] }}</td>
                                <td class="n"><b>{{ $fmt($row['registrations_period']) }}</b></td>
                                <td class="n" style="color:var(--muted)">{{ $fmt($row['registrations_prev']) }}</td>
                                <td class="n">{{ $fmt($row['registrations_all']) }}</td>
                                <td class="n">{{ $fmt($row['payers']) }}</td>
                                <td class="n">{{ $fmt($row['repeat_payers']) }}</td>
                                <td class="n">{{ $fmt($row['orders']) }}</td>
                                <td class="n"><b>{{ $fmt($row['revenue']) }} ₽</b></td>
                                <td class="n">{{ $row['arpu'] === null ? '—' : $fmt($row['arpu']).' ₽' }}</td>
                                <td class="n">{{ $row['ltv'] === null ? '—' : $fmt($row['ltv']).' ₽' }}</td>
                                <td class="n">{{ $row['avg_check'] === null ? '—' : $fmt($row['avg_check']).' ₽' }}</td>
                                <td class="n">{{ $row['days_to_pay'] === null ? '—' : str_replace('.', ',', (string) $row['days_to_pay']).' дн.' }}</td>
                                <td class="n">{{ $row['paid_in_trial'] === null ? '—' : $fmt($row['paid_in_trial']).' из '.$fmt($row['payers']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mk-card__note" style="margin-top:0.5rem">«На 1 регистрацию» — сколько в среднем приносит один пришедший по коду. «Оплатили в пробный» — первая оплата пришла до конца бесплатных дней. «Подключили устройство» по старым неоплатившим занижено: привязки неактивных аккаунтов очищены при переходе 12.10.2026.</div>

            <div class="mk-grid" style="margin:1.25rem 0 0; gap:1.25rem">
                <div>
                    <div class="mk-card__head" style="margin-bottom:0.5rem">
                        <div class="mk-sub-title" style="margin:0">Регистрации по кодам, по дням</div>
                        <div class="mk-legend">
                            @foreach ($pr['series'] as $name)
                                <span><i class="box" style="background: {{ $seriesColor[$name] }}"></i>{{ $name }}</span>
                            @endforeach
                        </div>
                    </div>
                    @if (empty($pr['series']))
                        <div class="mk-empty">За период регистраций по промокодам нет</div>
                    @else
                        <div class="mk-chart" id="mk-promo-bars" style="height:220px" role="img" aria-label="Столбчатый график регистраций по промокодам по дням"></div>
                    @endif
                </div>
                <div>
                    <div class="mk-sub-title" style="margin-top:0">Последние регистрации по промокодам</div>
                    @if (empty($pr['recent']))
                        <div class="mk-empty">За период регистраций по промокодам нет</div>
                    @else
                        <div class="mk-scroll" style="max-height:340px; overflow-y:auto">
                            <table class="mk-table">
                                <thead><tr><th>Когда</th><th>Кто</th><th>Код</th><th>Статус</th><th class="n">Оплатил</th></tr></thead>
                                <tbody>
                                    @foreach ($pr['recent'] as $r)
                                        <tr>
                                            <td style="white-space:nowrap">{{ $r['at']->format('d.m H:i') }}</td>
                                            <td>{{ $r['email'] }}</td>
                                            <td class="mk-code">{{ $r['code'] }}</td>
                                            <td><span class="mk-pill {{ $stateClass[$r['state']] ?? '' }}">{{ $r['state'] }}</span>@unless ($r['activated'])<span class="mk-pill" title="Устройство не подключено">без устройства</span>@endunless</td>
                                            <td class="n">{{ $r['paid'] > 0 ? $fmt($r['paid']).' ₽' : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="mk-grid mk-grid--2">
        <div class="mk-card">
            <div class="mk-card__head">
                <div class="mk-card__title">Откуда приходят</div>
                <div class="mk-card__note">регистрации за период по каналу; оплаты — этих же людей</div>
            </div>
            <div class="mk-scroll">
                <table class="mk-table">
                    <thead><tr><th>Канал</th><th style="width:30%">Доля</th><th class="n">Рег.</th><th class="n">Оплатили</th><th class="n">Конверсия</th><th class="n">Выручка</th></tr></thead>
                    <tbody>
                        @foreach ($data['sources'] as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td><div class="mk-bar" title="{{ $pct($row['share']) }}"><span style="width: {{ $row['registrations'] * 100 / $maxShare }}%"></span></div></td>
                                <td class="n"><b>{{ $fmt($row['registrations']) }}</b> <span style="color:var(--muted)">{{ $pct($row['share']) }}</span></td>
                                <td class="n">{{ $fmt($row['payers']) }}</td>
                                <td class="n">{{ $pct($row['conversion']) }}</td>
                                <td class="n">{{ $fmt($row['revenue']) }} ₽</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mk-card">
            <div class="mk-card__head"><div class="mk-card__title">Оплаты за период</div></div>
            <div class="mk-card__note">Новые платящие и продления</div>
            <div class="mk-split" aria-hidden="true">
                <span style="width: {{ $data['payments']['new_revenue'] * 100 / $payTotal }}%; background: var(--s1)"></span>
                <span style="width: {{ $data['payments']['repeat_revenue'] * 100 / $payTotal }}%; background: var(--s3)"></span>
            </div>
            <div class="mk-legend" style="margin-bottom:1rem">
                <span><i class="box" style="background:var(--s1)"></i>Новые — {{ $fmt($data['payments']['new_revenue']) }} ₽</span>
                <span><i class="box" style="background:var(--s3)"></i>Продления — {{ $fmt($data['payments']['repeat_revenue']) }} ₽</span>
            </div>
            <table class="mk-table">
                <thead><tr><th>Срок</th><th class="n">Оплат</th><th class="n">Выручка</th></tr></thead>
                <tbody>
                    @forelse ($data['payments']['by_term'] as $row)
                        <tr><td>{{ $row['label'] }}</td><td class="n">{{ $fmt($row['count']) }}</td><td class="n">{{ $fmt($row['revenue']) }} ₽</td></tr>
                    @empty
                        <tr><td colspan="3" class="mk-empty">Оплат нет</td></tr>
                    @endforelse
                </tbody>
            </table>
            <table class="mk-table" style="margin-top:0.75rem">
                <thead><tr><th>Где оплатили</th><th class="n">Оплат</th><th class="n">Выручка</th></tr></thead>
                <tbody>
                    @foreach ($data['payments']['by_channel'] as $row)
                        <tr><td>{{ $row['label'] }}</td><td class="n">{{ $fmt($row['count']) }}</td><td class="n">{{ $fmt($row['revenue']) }} ₽</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mk-card" style="margin-bottom:1rem">
        <div class="mk-card__head">
            <div class="mk-card__title">Реклама в Telegram-боте</div>
            <div class="mk-card__note">метки t.me/бот?start=МЕТКА · регистрации за период</div>
        </div>
        @if (empty($data['campaigns']))
            <div class="mk-empty">За период по рекламным меткам регистраций нет</div>
        @else
            <div class="mk-scroll">
                <table class="mk-table">
                    <thead><tr><th>Метка</th><th class="n">Рег.</th><th class="n">Оплатили</th><th class="n">Конверсия</th><th class="n">Выручка</th></tr></thead>
                    <tbody>
                        @foreach ($data['campaigns'] as $row)
                            <tr><td class="mk-code">{{ $row['campaign'] }}</td><td class="n"><b>{{ $fmt($row['registrations']) }}</b></td><td class="n">{{ $fmt($row['payers']) }}</td><td class="n">{{ $pct($row['conversion']) }}</td><td class="n">{{ $fmt($row['revenue']) }} ₽</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="mk-card">
        <details>
            <summary>Таблица по дням</summary>
            <div class="mk-scroll" style="margin-top:0.75rem">
                <table class="mk-table">
                    <thead><tr><th>Дата</th><th class="n">Регистрации</th><th class="n">из них по промокоду</th><th class="n">Оплаты</th><th class="n">Новые платящие</th><th class="n">Выручка</th></tr></thead>
                    <tbody>
                        @foreach (array_reverse($data['daily']) as $row)
                            <tr><td>{{ $row['label'] }}</td><td class="n">{{ $row['registrations'] }}</td><td class="n">{{ $row['promo'] }}</td><td class="n">{{ $row['payments'] }}</td><td class="n">{{ $row['new_payers'] }}</td><td class="n">{{ $fmt($row['revenue']) }} ₽</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </div>
</div>

<script>
(function () {
    const daily = @json($data['daily']);
    const css = getComputedStyle(document.querySelector('.mk'));
    const C = { s1: css.getPropertyValue('--s1').trim(), s2: css.getPropertyValue('--s2').trim(), s3: css.getPropertyValue('--s3').trim() };
    const NS = 'http://www.w3.org/2000/svg';
    const el = (tag, attrs, parent) => { const n = document.createElementNS(NS, tag); for (const k in attrs) n.setAttribute(k, attrs[k]); if (parent) parent.appendChild(n); return n; };
    const rub = (n) => n.toLocaleString('ru-RU') + ' ₽';
    const niceMax = (v) => { if (v <= 4) return 4; const p = Math.pow(10, Math.floor(Math.log10(v))); const s = [1, 2, 2.5, 5, 10].find((m) => m * p >= v / 4 * 1.0001) * p; return Math.ceil(v / s) * s; };

    function tooltip(box) {
        const t = document.createElement('div'); t.className = 'mk-tip'; box.appendChild(t);
        return {
            show(x, y, date, rows) {
                t.replaceChildren();
                const d = document.createElement('div'); d.className = 'd'; d.textContent = date; t.appendChild(d);
                rows.forEach((r) => {
                    const row = document.createElement('div'); row.className = 'r';
                    const i = document.createElement('i'); i.style.background = r.color;
                    const b = document.createElement('b'); b.textContent = r.value;
                    const s = document.createElement('span'); s.textContent = r.name;
                    row.append(i, b, s); t.appendChild(row);
                });
                const w = box.clientWidth, tw = t.offsetWidth;
                t.style.left = Math.min(Math.max(0, x + 12), w - tw) + 'px';
                t.style.top = Math.max(0, y - 10) + 'px';
                t.classList.add('on');
            },
            hide() { t.classList.remove('on'); },
        };
    }

    function frame(box, maxV, fmtY) {
        box.replaceChildren();
        const W = box.clientWidth, H = box.clientHeight;
        const m = { l: 44, r: 54, t: 10, b: 26 };
        const svg = el('svg', { viewBox: `0 0 ${W} ${H}`, height: H }, box);
        const iw = W - m.l - m.r, ih = H - m.t - m.b;
        const y = (v) => m.t + ih - (v / maxV) * ih;
        const grid = el('g', { class: 'grid' }, svg), axis = el('g', { class: 'axis' }, svg);
        for (let i = 0; i <= 4; i++) {
            const v = maxV * i / 4, yy = y(v);
            el('line', { x1: m.l, x2: W - m.r, y1: yy, y2: yy }, grid);
            const tx = el('text', { x: m.l - 8, y: yy + 4, 'text-anchor': 'end' }, axis); tx.textContent = fmtY(v);
        }
        const n = daily.length, every = Math.max(1, Math.ceil(n / Math.max(2, Math.floor(iw / 56))));
        const x = (i) => m.l + (n === 1 ? iw / 2 : (i * iw) / (n - 1));
        return { svg, W, H, m, iw, ih, x, y, every, axis };
    }

    function lines() {
        const box = document.getElementById('mk-lines');
        const series = [
            { key: 'registrations', name: 'Регистрации', color: C.s1 },
            { key: 'payments', name: 'Оплаты', color: C.s2 },
        ];
        const maxV = niceMax(Math.max(1, ...daily.flatMap((d) => series.map((s) => d[s.key]))));
        const f = frame(box, maxV, (v) => Math.round(v).toLocaleString('ru-RU'));
        daily.forEach((d, i) => { if (i % f.every === 0 || i === daily.length - 1) { const t = el('text', { x: f.x(i), y: f.H - 6, 'text-anchor': 'middle' }, f.axis); t.textContent = d.label; } });
        const last = daily[daily.length - 1];
        // Подписи на концах линий: не даём им наложиться, если значения близки.
        const ends = series.map((s) => ({ s, y: f.y(last[s.key]) + 4 })).sort((a, b) => a.y - b.y);
        for (let i = 1; i < ends.length; i++) if (ends[i].y - ends[i - 1].y < 13) ends[i].y = ends[i - 1].y + 13;
        series.forEach((s) => {
            const pts = daily.map((d, i) => `${f.x(i)},${f.y(d[s.key])}`).join(' ');
            el('polyline', { points: pts, fill: 'none', stroke: s.color, 'stroke-width': 2, 'stroke-linejoin': 'round', 'stroke-linecap': 'round' }, f.svg);
        });
        ends.forEach(({ s, y }) => {
            const lbl = el('text', { x: f.x(daily.length - 1) + 8, y, class: 'end-label' }, f.svg);
            lbl.textContent = s.name.slice(0, 4) + '. ' + last[s.key];
        });
        const hair = el('line', { y1: f.m.t, y2: f.m.t + f.ih, stroke: '#94a3b8', 'stroke-width': 1, opacity: 0 }, f.svg);
        const dots = series.map((s) => el('circle', { r: 4.5, fill: s.color, stroke: '#fff', 'stroke-width': 2, opacity: 0 }, f.svg));
        const tip = tooltip(box);
        const hit = el('rect', { x: f.m.l, y: 0, width: f.iw, height: f.H, fill: 'transparent' }, f.svg);
        const at = (evt) => {
            const r = f.svg.getBoundingClientRect(), px = (evt.clientX - r.left) * (f.W / r.width);
            const i = Math.max(0, Math.min(daily.length - 1, Math.round(((px - f.m.l) / f.iw) * (daily.length - 1))));
            const d = daily[i], xx = f.x(i);
            hair.setAttribute('x1', xx); hair.setAttribute('x2', xx); hair.setAttribute('opacity', 1);
            series.forEach((s, k) => { dots[k].setAttribute('cx', xx); dots[k].setAttribute('cy', f.y(d[s.key])); dots[k].setAttribute('opacity', 1); });
            tip.show(xx * (r.width / f.W), 10, d.label, [
                ...series.map((s) => ({ color: s.color, value: d[s.key].toLocaleString('ru-RU'), name: s.name })),
                { color: '#94a3b8', value: d.promo.toLocaleString('ru-RU'), name: 'рег. по промокоду' },
            ]);
        };
        hit.addEventListener('pointermove', at);
        hit.addEventListener('pointerleave', () => { tip.hide(); hair.setAttribute('opacity', 0); dots.forEach((d) => d.setAttribute('opacity', 0)); });
    }

    function bars() {
        const box = document.getElementById('mk-bars');
        const maxV = niceMax(Math.max(1, ...daily.map((d) => d.revenue)));
        const f = frame(box, maxV, (v) => v >= 1000 ? Math.round(v / 1000) + ' тыс' : Math.round(v));
        const slot = f.iw / daily.length, bw = Math.max(2, Math.min(28, slot - 2));
        const tip = tooltip(box);
        daily.forEach((d, i) => {
            const cx = f.m.l + slot * i + slot / 2;
            if (i % f.every === 0 || i === daily.length - 1) { const t = el('text', { x: cx, y: f.H - 6, 'text-anchor': 'middle' }, f.axis); t.textContent = d.label; }
            const h = (d.revenue / maxV) * f.ih;
            if (h > 0) {
                const r = Math.min(4, bw / 2, h);
                const x0 = cx - bw / 2, y0 = f.m.t + f.ih - h, yb = f.m.t + f.ih;
                el('path', { d: `M${x0},${yb} V${y0 + r} Q${x0},${y0} ${x0 + r},${y0} H${x0 + bw - r} Q${x0 + bw},${y0} ${x0 + bw},${y0 + r} V${yb} Z`, fill: C.s3, class: 'bar' }, f.svg);
            }
            const hit = el('rect', { x: f.m.l + slot * i, y: f.m.t, width: slot, height: f.ih, fill: 'transparent', tabindex: 0 }, f.svg);
            const show = () => {
                const r = f.svg.getBoundingClientRect();
                tip.show(cx * (r.width / f.W), 10, d.label, [
                    { color: C.s3, value: rub(d.revenue), name: 'выручка' },
                    { color: '#94a3b8', value: d.payments.toLocaleString('ru-RU'), name: 'оплат' },
                    { color: '#94a3b8', value: d.new_payers.toLocaleString('ru-RU'), name: 'новых платящих' },
                ]);
            };
            hit.addEventListener('pointerenter', show); hit.addEventListener('focus', show);
            hit.addEventListener('pointerleave', () => tip.hide()); hit.addEventListener('blur', () => tip.hide());
        });
    }

    const promo = @json($pr['daily'] ?? []), promoSeries = @json($pr['series'] ?? []), promoColors = @json(array_values($seriesColor ?? []));
    function promoBars() {
        const box = document.getElementById('mk-promo-bars');
        if (!box || !promoSeries.length) return;
        const colors = promoColors.map((c) => c.startsWith('var(') ? css.getPropertyValue(c.slice(4, -1)).trim() : c);
        const totals = promo.map((d) => promoSeries.reduce((a, k) => a + d[k], 0));
        const maxV = niceMax(Math.max(1, ...totals));
        box.replaceChildren();
        const W = box.clientWidth, H = box.clientHeight, m = { l: 34, r: 8, t: 8, b: 24 };
        const svg = el('svg', { viewBox: `0 0 ${W} ${H}`, height: H }, box);
        const iw = W - m.l - m.r, ih = H - m.t - m.b, y = (v) => m.t + ih - (v / maxV) * ih;
        const grid = el('g', { class: 'grid' }, svg), axis = el('g', { class: 'axis' }, svg);
        for (let i = 0; i <= 4; i++) { const v = maxV * i / 4; el('line', { x1: m.l, x2: W - m.r, y1: y(v), y2: y(v) }, grid); const t = el('text', { x: m.l - 6, y: y(v) + 4, 'text-anchor': 'end' }, axis); t.textContent = Math.round(v); }
        const slot = iw / promo.length, bw = Math.max(2, Math.min(22, slot - 2));
        const every = Math.max(1, Math.ceil(promo.length / Math.max(2, Math.floor(iw / 56))));
        const tip = tooltip(box);
        promo.forEach((d, i) => {
            const cx = m.l + slot * i + slot / 2;
            if (i % every === 0 || i === promo.length - 1) { const t = el('text', { x: cx, y: H - 6, 'text-anchor': 'middle' }, axis); t.textContent = d.label; }
            let acc = 0;
            promoSeries.forEach((k, j) => {
                const v = d[k]; if (!v) return;
                const y0 = y(acc + v), y1 = y(acc);
                // 2px просвет между сегментами стопки.
                el('rect', { x: cx - bw / 2, y: y0, width: bw, height: Math.max(1, y1 - y0 - (acc > 0 ? 2 : 0)), rx: Math.min(2, bw / 4), fill: colors[j] }, svg);
                acc += v;
            });
            const hit = el('rect', { x: m.l + slot * i, y: m.t, width: slot, height: ih, fill: 'transparent', tabindex: 0 }, svg);
            const show = () => {
                const r = svg.getBoundingClientRect();
                tip.show(cx * (r.width / W), 8, d.label, [
                    ...promoSeries.map((k, j) => ({ color: colors[j], value: String(d[k]), name: k })),
                    { color: '#94a3b8', value: String(totals[i]), name: 'всего' },
                ]);
            };
            hit.addEventListener('pointerenter', show); hit.addEventListener('focus', show);
            hit.addEventListener('pointerleave', () => tip.hide()); hit.addEventListener('blur', () => tip.hide());
        });
    }
    const draw = () => { lines(); bars(); promoBars(); };
    draw();
    let t; window.addEventListener('resize', () => { clearTimeout(t); t = setTimeout(draw, 150); });
})();
</script>
@endsection

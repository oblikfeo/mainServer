@extends('layouts.admin')

@section('title', 'Рекламные кампании')

@php
    $num = static fn ($v) => number_format((int) $v, 0, ',', ' ');
@endphp

@section('content')
    <form
        method="get"
        action="{{ route('admin.campaigns') }}"
        class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:flex-wrap sm:items-end gap-4 rounded-2xl border border-slate-200/90 bg-white px-4 sm:px-5 py-4 shadow-md shadow-slate-200/40 ring-1 ring-slate-900/5"
    >
        <div class="w-full sm:w-auto sm:min-w-[10rem]">
            <label for="date_from" class="block text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 mb-2">Пришли с</label>
            <input
                type="date"
                name="date_from"
                id="date_from"
                value="{{ $dateFrom }}"
                class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400 min-h-[44px]"
            >
        </div>
        <div class="w-full sm:w-auto sm:min-w-[10rem]">
            <label for="date_to" class="block text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 mb-2">по</label>
            <input
                type="date"
                name="date_to"
                id="date_to"
                value="{{ $dateTo }}"
                class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400 min-h-[44px]"
            >
        </div>
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto sm:items-end">
            <button type="submit" class="w-full sm:w-auto rounded-xl bg-slate-900 text-white px-5 py-3 sm:py-2.5 text-sm font-bold shadow-sm hover:bg-slate-800 transition-colors min-h-[44px] sm:min-h-0">
                Показать
            </button>
            <a
                href="{{ route('admin.campaigns.export', request()->only(['date_from', 'date_to'])) }}"
                class="w-full sm:w-auto text-center rounded-xl border border-slate-300 px-5 py-3 sm:py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors min-h-[44px] sm:min-h-0"
            >
                Выгрузить CSV
            </a>
            @if ($dateFrom !== '' || $dateTo !== '')
                <a href="{{ route('admin.campaigns') }}" class="text-center sm:text-left text-sm font-semibold text-slate-600 hover:text-slate-900 py-2 sm:py-2.5">Сбросить</a>
            @endif
        </div>
    </form>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 sm:mb-8">
        @foreach ([
            ['Переходы', $num($totals['clicks']), 'нажали /start по ссылке'],
            ['Регистрации', $num($totals['registrations']), 'создали аккаунт'],
            ['Покупатели', $num($totals['buyers']), $totals['conversion'] === null ? 'нет переходов' : 'конверсия '.number_format($totals['conversion'], 1, ',', ' ').'%'],
            ['Выручка', $num($totals['revenue']).' ₽', $num($totals['orders']).' оплат'],
        ] as [$label, $value, $hint])
            <div class="rounded-2xl border border-slate-200/90 bg-white px-4 sm:px-5 py-4 shadow-sm ring-1 ring-slate-900/5">
                <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">{{ $label }}</div>
                <div class="mt-1 text-2xl sm:text-3xl font-black tabular-nums text-slate-900">{{ $value }}</div>
                <div class="mt-1 text-xs text-slate-500">{{ $hint }}</div>
            </div>
        @endforeach
    </div>

    <article class="rounded-3xl border-2 border-slate-200/90 bg-white shadow-xl shadow-slate-300/25 overflow-hidden mb-6 sm:mb-8">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex flex-wrap items-center justify-between gap-3">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">
                Воронка по кампаниям · {{ $num($totals['campaigns']) }}
            </div>
            <div class="text-xs text-slate-500">Выручка — все оплаты пришедшей когорты, а не только за выбранный период</div>
        </div>

        @if ($rows === [])
            <div class="p-6 sm:p-8 text-slate-600">
                Пока ни одного перехода с меткой. Запустите рекламу со ссылкой из блока ниже — данные появятся здесь.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th class="px-4 sm:px-5 py-3 text-left">Кампания</th>
                            <th class="px-4 sm:px-5 py-3 text-right">Переходы</th>
                            <th class="px-4 sm:px-5 py-3 text-right">Регистрации</th>
                            <th class="px-4 sm:px-5 py-3 text-right">Триалы</th>
                            <th class="px-4 sm:px-5 py-3 text-right">Покупатели</th>
                            <th class="px-4 sm:px-5 py-3 text-right">Выручка</th>
                            <th class="px-4 sm:px-5 py-3 text-right">Конверсия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rows as $row)
                            <tr class="align-top hover:bg-slate-50/70">
                                <td class="px-4 sm:px-5 py-3">
                                    <span class="font-mono font-bold text-slate-900 break-all">{{ $row['campaign'] }}</span>
                                    @if (($breakdown[$row['campaign']] ?? []) !== [])
                                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                                            @foreach ($breakdown[$row['campaign']] as $item)
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-700">
                                                    {{ $item->plan }} · {{ $item->period }} — {{ $num($item->orders) }} × {{ $num($item->revenue) }}&nbsp;₽
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 sm:px-5 py-3 text-right tabular-nums text-slate-900">{{ $num($row['clicks']) }}</td>
                                <td class="px-4 sm:px-5 py-3 text-right tabular-nums text-slate-900">{{ $num($row['registrations']) }}</td>
                                <td class="px-4 sm:px-5 py-3 text-right tabular-nums text-slate-600">{{ $num($row['trials']) }}</td>
                                <td class="px-4 sm:px-5 py-3 text-right tabular-nums font-bold text-slate-900">{{ $num($row['buyers']) }}</td>
                                <td class="px-4 sm:px-5 py-3 text-right tabular-nums font-bold text-slate-900 whitespace-nowrap">{{ $num($row['revenue']) }}&nbsp;₽</td>
                                <td class="px-4 sm:px-5 py-3 text-right tabular-nums {{ $row['conversion'] === null ? 'text-slate-400' : 'text-slate-900' }}">
                                    {{ $row['conversion'] === null ? '—' : number_format($row['conversion'], 1, ',', ' ').'%' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </article>

    <article class="rounded-3xl border-2 border-slate-200/90 bg-white shadow-xl shadow-slate-300/25 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Как сделать ссылку для ролика</div>
        </div>
        <div class="p-5 sm:p-6 space-y-4 text-sm text-slate-700">
            @if ($botUsername === '')
                <p class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-amber-900">
                    В <span class="font-mono">.env</span> не задан <span class="font-mono">TELEGRAM_LINK_BOT_USERNAME</span> — подставьте имя бота в ссылку вручную.
                </p>
            @endif

            <div class="rounded-2xl bg-slate-900 px-4 py-3.5 font-mono text-sm text-slate-100 break-all">
                https://t.me/{{ $botUsername !== '' ? $botUsername : 'ИМЯ_БОТА' }}?start=<span class="text-amber-300">ad_reels_0901</span>
            </div>

            <p>Метка после <span class="font-mono">?start=</span> — любая своя: одна на ролик, площадку или блогера. Она попадает в таблицу выше автоматически.</p>

            <ul class="space-y-2 list-disc pl-5">
                <li><strong>Только</strong> латиница, цифры, <span class="font-mono">_</span> и <span class="font-mono">-</span>. Точки, пробелы и кириллица Telegram не пропустит.</li>
                <li><strong>Короче 48 символов</strong> — иначе бот примет метку за токен привязки аккаунта, и реклама сломается.</li>
                <li>Начинайте с <span class="font-mono">ad_</span>: реферальный код — ровно 8 строчных символов, префикс гарантирует, что метка не столкнётся с чужой реферальной ссылкой.</li>
                <li>Человек засчитывается в кампанию, если зарегистрировался в течение {{ $windowHours }} ч после перехода (<span class="font-mono">CAMPAIGN_ATTRIBUTION_WINDOW_HOURS</span>).</li>
            </ul>

            <p class="text-slate-500">
                Примеры: <span class="font-mono">ad_reels_0901</span>, <span class="font-mono">ad_shorts_0901</span>, <span class="font-mono">ad_tg_bloger1</span>.
                «Переходы» считают уникальные Telegram-аккаунты, включая тех, кто до регистрации не дошёл — по разрыву между переходами и регистрациями видно, где отваливается воронка.
            </p>
        </div>
    </article>
@endsection

@extends('layouts.admin')

@section('title', 'Реферальная система')

@section('content')
    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← В меню
    </a>

    <div class="mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Реферальная система</h1>
        <p class="mt-2 text-sm sm:text-base text-slate-600 max-w-3xl">
            Кто кого пригласил, сколько зарегистрировалось и оплатило. Партнёрские лендинги привязаны к отдельным аккаунтам-реферерам.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
        <div class="rounded-2xl border border-slate-200/90 bg-white px-5 py-4 shadow-sm ring-1 ring-slate-900/5">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Рефереров</div>
            <div class="mt-1 text-2xl font-black tabular-nums text-slate-900">{{ number_format($stats['referrers'], 0, ',', ' ') }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200/90 bg-white px-5 py-4 shadow-sm ring-1 ring-slate-900/5">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Приглашённых</div>
            <div class="mt-1 text-2xl font-black tabular-nums text-slate-900">{{ number_format($stats['referred'], 0, ',', ' ') }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200/90 bg-white px-5 py-4 shadow-sm ring-1 ring-slate-900/5">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">С оплатой</div>
            <div class="mt-1 text-2xl font-black tabular-nums text-slate-900">{{ number_format($stats['paid'], 0, ',', ' ') }}</div>
        </div>
    </div>

    @if ($partners !== [])
        <div class="rounded-3xl border-2 border-slate-200/90 bg-white shadow-xl shadow-slate-300/25 overflow-hidden mb-6 sm:mb-8">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Партнёрские программы</div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse min-w-[48rem]">
                    <thead>
                        <tr class="bg-slate-900 text-white">
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Партнёр</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Лендинг</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 min-w-[12rem]" scope="col">Аккаунт-реферер</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap text-right" scope="col">Рег.</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap text-right" scope="col">Оплат</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap text-right" scope="col">Актив.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($partners as $p)
                            <tr class="{{ $loop->iteration % 2 === 0 ? 'bg-slate-50/50' : 'bg-white' }}">
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $p['display_name'] }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $p['route'] ?: '—' }}</td>
                                <td class="px-4 py-3 text-slate-800">
                                    @if ($p['user'])
                                        <div>{{ $p['email'] }}</div>
                                        <div class="text-xs text-slate-500 font-mono">{{ $p['user']->referral_code }}</div>
                                    @else
                                        <span class="text-rose-700 font-semibold">Аккаунт не найден</span>
                                        <div class="text-xs text-slate-500">{{ $p['email'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums font-bold">{{ $p['registered'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-bold">{{ $p['paid'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-bold">{{ $p['active'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <form
        method="get"
        action="{{ route('admin.referral') }}"
        class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:flex-wrap sm:items-end gap-4 rounded-2xl border border-slate-200/90 bg-white px-4 sm:px-5 py-4 shadow-md shadow-slate-200/40 ring-1 ring-slate-900/5"
    >
        <div class="w-full sm:flex-1 sm:min-w-[12rem]">
            <label for="q" class="block text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 mb-2">Поиск</label>
            <input
                type="search"
                name="q"
                id="q"
                value="{{ $search }}"
                placeholder="email, имя, код"
                class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400 min-h-[44px]"
            >
        </div>
        <div class="w-full sm:flex-1 sm:min-w-[12rem]">
            <label for="referrer" class="block text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 mb-2">Фильтр по рефереру</label>
            <input
                type="search"
                name="referrer"
                id="referrer"
                value="{{ $referrerFilter }}"
                placeholder="email реферера"
                class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400 min-h-[44px]"
            >
        </div>
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto sm:items-end">
            <button type="submit" class="w-full sm:w-auto rounded-xl bg-slate-900 text-white px-5 py-3 sm:py-2.5 text-sm font-bold shadow-sm hover:bg-slate-800 transition-colors min-h-[44px] sm:min-h-0">
                Найти
            </button>
            @if ($search !== '' || $referrerFilter !== '')
                <a href="{{ route('admin.referral') }}" class="text-center sm:text-left text-sm font-semibold text-slate-600 hover:text-slate-900 py-2 sm:py-2.5">Сбросить</a>
            @endif
        </div>
    </form>

    <div class="rounded-3xl border-2 border-slate-200/90 bg-white shadow-xl shadow-slate-300/25 overflow-hidden mb-6 sm:mb-8">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Топ рефереров</div>
        </div>
        @if ($referrers->isEmpty())
            <p class="px-6 py-10 text-center text-slate-500 text-sm">Рефереров не найдено.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse min-w-[56rem]">
                    <thead>
                        <tr class="bg-slate-900 text-white">
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 min-w-[12rem]" scope="col">Реферер</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Код</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap text-right" scope="col">Рег.</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap text-right" scope="col">Оплат</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap text-right" scope="col">Актив.</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 min-w-[14rem]" scope="col">Ссылка</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($referrers as $r)
                            <tr class="{{ $loop->iteration % 2 === 0 ? 'bg-slate-50/50' : 'bg-white' }} hover:bg-slate-100/80 transition-colors">
                                <td class="px-4 py-3 text-slate-900">
                                    <div class="font-semibold">{{ $r->name }}</div>
                                    <div class="text-xs text-slate-600">{{ $r->email }}</div>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-800">{{ $r->referral_code }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-bold">{{ $r->referrals_count }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-bold">{{ $r->referrals_paid_count }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-bold">{{ $r->referrals_active_count }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-700 break-all">
                                    <a href="{{ $r->referral_url }}" class="underline underline-offset-2 hover:text-slate-900" target="_blank" rel="noopener noreferrer">{{ $r->referral_url }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-slate-200">{{ $referrers->links() }}</div>
        @endif
    </div>

    <div class="rounded-3xl border-2 border-slate-200/90 bg-white shadow-xl shadow-slate-300/25 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Последние приглашённые</div>
        </div>
        @if ($recentReferrals->isEmpty())
            <p class="px-6 py-10 text-center text-slate-500 text-sm">Приглашённых не найдено.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse min-w-[56rem]">
                    <thead>
                        <tr class="bg-slate-900 text-white">
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Регистрация</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 min-w-[12rem]" scope="col">Приглашённый</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 min-w-[12rem]" scope="col">Пригласил</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Статус</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($recentReferrals as $ref)
                            @php
                                $badge = match ($ref->ref_status_kind) {
                                    'ok' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200/80',
                                    'paid' => 'bg-sky-100 text-sky-800 ring-1 ring-sky-200/80',
                                    default => 'bg-slate-200/80 text-slate-700',
                                };
                            @endphp
                            <tr class="{{ $loop->iteration % 2 === 0 ? 'bg-slate-50/50' : 'bg-white' }} hover:bg-slate-100/80 transition-colors">
                                <td class="px-4 py-3 text-slate-800 tabular-nums whitespace-nowrap">{{ $ref->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-900">
                                    <div class="font-semibold">{{ $ref->name }}</div>
                                    <div class="text-xs text-slate-600">{{ $ref->email }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-800">
                                    @if ($ref->referrer)
                                        <div class="font-semibold">{{ $ref->referrer->name }}</div>
                                        <div class="text-xs text-slate-600">{{ $ref->referrer->email }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $badge }}">{{ $ref->ref_status }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-slate-200">{{ $recentReferrals->links() }}</div>
        @endif
    </div>
@endsection

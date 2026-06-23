@extends('layouts.admin')

@section('title', '')

@section('content')
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
                placeholder="email или имя"
                class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400 min-h-[44px]"
            >
        </div>
        <div class="w-full sm:flex-1 sm:min-w-[12rem]">
            <label for="referrer" class="block text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 mb-2">Реферер</label>
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
        @foreach ($partners as $p)
            <div class="rounded-2xl border-2 border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Партнёр</div>
                <div class="mt-2 text-2xl sm:text-3xl font-black text-slate-900">{{ $p['display_name'] }}</div>
                <div class="mt-3 text-sm text-slate-600">
                    <span class="font-semibold text-slate-800">Лендинг:</span>
                    <span class="font-mono">{{ $p['route'] ?: '—' }}</span>
                </div>
                <div class="mt-1 text-sm text-slate-600">
                    <span class="font-semibold text-slate-800">Реферер:</span>
                    @if ($p['user'])
                        {{ $p['email'] }}
                    @else
                        <span class="text-rose-700 font-semibold">аккаунт не найден</span>
                        <span class="text-slate-500">({{ $p['email'] }})</span>
                    @endif
                </div>
                <div class="mt-6 grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Регистрации</div>
                        <div class="mt-1 text-2xl font-black tabular-nums text-slate-900">{{ $p['registered'] }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Оплаты</div>
                        <div class="mt-1 text-2xl font-black tabular-nums text-slate-900">{{ $p['paid'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="rounded-2xl border-2 border-slate-200 bg-white p-6 sm:p-8 shadow-sm {{ $partners === [] ? 'lg:col-span-2' : '' }}">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Топ рефереров</div>
            @if ($topReferrers->isEmpty())
                <p class="mt-4 text-sm text-slate-500">Пока никого нет.</p>
            @else
                <ul class="mt-4 space-y-4">
                    @foreach ($topReferrers as $r)
                        <li class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-4 last:border-0 last:pb-0">
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900 truncate">{{ $r->name }}</div>
                                <div class="text-sm text-slate-600 truncate">{{ $r->email }}</div>
                            </div>
                            <div class="flex shrink-0 gap-4 text-sm tabular-nums">
                                <span><span class="text-slate-500">рег.</span> <span class="font-bold text-slate-900">{{ $r->referrals_count }}</span></span>
                                <span><span class="text-slate-500">оплат</span> <span class="font-bold text-slate-900">{{ $r->referrals_paid_count }}</span></span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="rounded-3xl border-2 border-slate-200/90 bg-white shadow-xl shadow-slate-300/25 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Приглашённые</div>
        </div>
        @if ($recentReferrals->isEmpty())
            <p class="px-6 py-10 text-center text-slate-500 text-sm">Никого не найдено.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse min-w-[48rem]">
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
                                $badge = $ref->ref_status_kind === 'paid'
                                    ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200/80'
                                    : 'bg-slate-200/80 text-slate-700';
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

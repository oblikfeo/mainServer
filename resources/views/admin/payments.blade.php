@extends('layouts.admin')

@section('title', '')

@section('content')
    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← В меню
    </a>

    <form
        method="get"
        action="{{ route('admin.payments') }}"
        class="mb-6 sm:mb-8 flex flex-col sm:flex-row sm:flex-wrap sm:items-end gap-4 rounded-2xl border border-slate-200/90 bg-white px-4 sm:px-5 py-4 shadow-md shadow-slate-200/40 ring-1 ring-slate-900/5"
    >
        <div class="w-full sm:w-auto sm:min-w-[10rem]">
            <label for="date_from" class="block text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 mb-2">С даты</label>
            <input
                type="date"
                name="date_from"
                id="date_from"
                value="{{ $dateFrom }}"
                class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400 min-h-[44px]"
            >
        </div>
        <div class="w-full sm:w-auto sm:min-w-[10rem]">
            <label for="date_to" class="block text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 mb-2">По дату</label>
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
            @if ($dateFrom !== '' || $dateTo !== '')
                <a href="{{ route('admin.payments') }}" class="text-center sm:text-left text-sm font-semibold text-slate-600 hover:text-slate-900 py-2 sm:py-2.5">Сбросить</a>
            @endif
        </div>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
        <div class="rounded-2xl border border-slate-200/90 bg-white px-5 py-4 shadow-sm ring-1 ring-slate-900/5">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Оплат</div>
            <div class="mt-1 text-2xl font-black tabular-nums text-slate-900">{{ number_format($totalCount, 0, ',', ' ') }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200/90 bg-white px-5 py-4 shadow-sm ring-1 ring-slate-900/5">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Сумма</div>
            <div class="mt-1 text-2xl font-black tabular-nums text-slate-900">{{ number_format($totalRub, 0, ',', ' ') }} ₽</div>
        </div>
    </div>

    <div class="rounded-3xl border-2 border-slate-200/90 bg-white shadow-xl shadow-slate-300/25 overflow-hidden mb-6 sm:mb-8">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Заказы оплаты (WATA)</div>
        </div>
        @if (($orders ?? null) && $orders->isEmpty())
            <p class="px-6 py-10 text-center text-slate-500 text-sm">Заказов нет.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse min-w-[72rem]">
                    <thead>
                        <tr class="bg-slate-900 text-white">
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Создан</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Order ID</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Статус</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap text-right" scope="col">Сумма</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Тариф</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 min-w-[12rem]" scope="col">Пользователь</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Link ID</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Transaction ID</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($orders as $o)
                            @php
                                $status = (string) ($o->status ?? '');
                                $badge = match ($status) {
                                    'paid' => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200/80',
                                    'declined' => 'bg-rose-100 text-rose-800 ring-1 ring-rose-200/80',
                                    default => 'bg-slate-200/80 text-slate-700',
                                };
                            @endphp
                            <tr class="{{ $loop->iteration % 2 === 0 ? 'bg-slate-50/50' : 'bg-white' }} hover:bg-slate-100/80 transition-colors">
                                <td class="px-4 py-3 text-slate-800 tabular-nums whitespace-nowrap">{{ $o->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-800 whitespace-nowrap">{{ $o->order_id }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $badge }}">{{ $status }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-900 font-bold tabular-nums whitespace-nowrap text-right">{{ number_format((int) $o->amount_rub, 0, ',', ' ') }} ₽</td>
                                <td class="px-4 py-3 text-slate-800 text-xs whitespace-nowrap">{{ $o->tariff_plan }} · {{ $o->tariff_period }}</td>
                                <td class="px-4 py-3 text-slate-800 text-xs break-all" title="{{ $o->user?->email ?? '' }}">{{ $o->user?->email ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-600 whitespace-nowrap">{{ $o->provider_link_id ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-600 whitespace-nowrap">{{ $o->provider_transaction_id ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="border-t border-slate-200 bg-white px-2 sm:px-4 py-3 sm:py-4 overflow-x-auto">
                    <div class="flex justify-center lg:justify-start min-w-max sm:min-w-0">
                        {{ $orders->links() }}
                    </div>
                </div>
            @endif
        @endif
    </div>

    <article class="rounded-3xl border-2 border-slate-200/90 bg-white shadow-xl shadow-slate-300/25 overflow-hidden">
        @if ($purchases->isEmpty())
            <p class="px-6 py-16 text-center text-slate-500 text-base bg-slate-50/50">Платежей нет за выбранный период.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse min-w-[56rem]">
                    <thead>
                        <tr class="bg-slate-900 text-white">
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Дата</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap text-right" scope="col">Сумма</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90" scope="col">Валюта</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 min-w-[12rem]" scope="col">Пользователь</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90" scope="col">Описание</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($purchases as $p)
                            <tr class="{{ $loop->iteration % 2 === 0 ? 'bg-slate-50/50' : 'bg-white' }} hover:bg-slate-100/80 transition-colors">
                                <td class="px-4 py-3 text-slate-800 tabular-nums whitespace-nowrap">
                                    {{ $p->paid_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-slate-900 font-bold tabular-nums whitespace-nowrap text-right">
                                    {{ number_format((int) $p->amount_rub, 0, ',', ' ') }} ₽
                                </td>
                                <td class="px-4 py-3 text-slate-800 font-semibold tabular-nums whitespace-nowrap">
                                    {{ $p->currency ?? 'RUB' }}
                                </td>
                                <td class="px-4 py-3 text-slate-800 text-xs break-all" title="{{ $p->user?->email ?? '' }}">
                                    {{ $p->user?->email ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $p->description ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </article>

    @if ($purchases->hasPages())
        <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-2 sm:px-4 py-3 sm:py-4 overflow-x-auto shadow-sm ring-1 ring-slate-900/5">
            <div class="flex justify-center lg:justify-start min-w-max sm:min-w-0">
                {{ $purchases->links() }}
            </div>
        </div>
    @endif
@endsection


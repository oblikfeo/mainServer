@extends('layouts.admin')

@section('title', '')

@section('content')
    <style>[x-cloak]{display:none!important}</style>
    <a href="{{ route('admin.dashboard') }}" class="inline-block text-slate-600 hover:text-slate-900 mb-6 sm:mb-8 text-base sm:text-lg font-medium">
        ←
    </a>

    @if (session('status'))
        <div class="mb-5 sm:mb-6 rounded-2xl border border-emerald-200/90 bg-emerald-50 px-4 sm:px-5 py-4 text-emerald-950 text-sm font-medium shadow-sm ring-1 ring-emerald-900/5">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->has('xui'))
        <div class="mb-5 sm:mb-6 rounded-2xl border border-rose-200/90 bg-rose-50 px-4 sm:px-5 py-4 text-rose-950 text-sm shadow-sm ring-1 ring-rose-900/5">
            {{ $errors->first('xui') }}
        </div>
    @endif

    @if (! empty($trafficErrors))
        <div class="mb-5 sm:mb-6 rounded-2xl border border-amber-200/90 bg-amber-50 px-4 sm:px-5 py-4 text-amber-950 text-sm shadow-sm ring-1 ring-amber-900/5 space-y-2">
            <p class="font-bold text-amber-900">Трафик с панелей частично или полностью недоступен</p>
            <ul class="list-disc list-inside text-amber-900/90 space-y-0.5">
                @foreach ($trafficErrors as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="get"
        action="{{ route('admin.report') }}"
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
                <a href="{{ route('admin.report') }}" class="text-center sm:text-left text-sm font-semibold text-slate-600 hover:text-slate-900 py-2 sm:py-2.5">Сбросить</a>
            @endif
        </div>
    </form>

    {{-- Мобильная вёрстка: только до lg (hidden + max-lg:block надёжнее, чем одно lg:hidden в CSS-бандле) --}}
    <div class="hidden max-lg:block space-y-4">
        @forelse ($subscriptions as $subscription)
            @php
                $order = config('xui.bundle_order', ['fi', 'nl']);
                $sums = [];
                foreach ($order as $bundleKey) {
                    $col = $bundleKey.'_sub_id';
                    $sid = $subscription->{$col} ?? null;
                    if (! is_string($sid) || $sid === '') {
                        continue;
                    }
                    $t = ($trafficMaps[$bundleKey] ?? [])[$sid] ?? null;
                    if ($t) {
                        $sums[] = $t['up'] + $t['down'];
                    }
                }
                $totalUsed = $sums === [] ? null : array_sum($sums);
                $exp = $subscription->expiresAt();
            @endphp
            <article
                class="rounded-2xl border-2 border-slate-200/90 bg-white shadow-lg shadow-slate-200/30 overflow-hidden"
                x-data="{ open: false }"
            >
                <div class="bg-slate-900 text-white px-4 py-3 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="font-mono text-sm font-bold tabular-nums shrink-0">#{{ $subscription->id }}</span>
                        <span class="text-white/70 text-xs truncate hidden min-[400px]:inline">подписка</span>
                    </div>
                    @if ($exp === null)
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-white/15 text-white ring-1 ring-white/25">—</span>
                    @elseif ($subscription->isExpired())
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/90 text-white">Истекла</span>
                    @else
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/90 text-white">Активна</span>
                    @endif
                </div>
                <div class="p-4 space-y-4">
                    <dl class="grid grid-cols-1 gap-3 text-sm">
                        <div class="flex justify-between gap-3 py-2 border-b border-slate-100">
                            <dt class="text-slate-500 font-medium shrink-0">Старт</dt>
                            <dd class="text-slate-900 font-semibold tabular-nums text-right break-words">{{ $subscription->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2 border-b border-slate-100">
                            <dt class="text-slate-500 font-medium shrink-0">Окончание</dt>
                            <dd class="text-slate-900 font-semibold tabular-nums text-right break-words">{{ $exp ? $exp->timezone(config('app.timezone'))->format('d.m.Y H:i') : '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2 border-b border-slate-100">
                            <dt class="text-slate-500 font-medium shrink-0">Квота</dt>
                            <dd class="text-slate-900 font-semibold tabular-nums">{{ $subscription->quota_gb }} ГБ</dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2">
                            <dt class="text-slate-500 font-medium shrink-0">Трафик</dt>
                            <dd class="text-slate-900 font-semibold tabular-nums text-right">{{ $totalUsed !== null ? $byteFmt($totalUsed) : '—' }}</dd>
                        </div>
                    </dl>
                    <button
                        type="button"
                        class="w-full flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 py-3 text-sm font-bold text-slate-800 hover:bg-slate-100 active:bg-slate-200 transition-colors min-h-[48px]"
                        @click="open = ! open"
                        :aria-expanded="open"
                    >
                        <span x-show="! open">Подробнее</span>
                        <span x-show="open" x-cloak>Свернуть</span>
                        <span class="text-slate-500" aria-hidden="true">▾</span>
                    </button>
                    <div
                        x-show="open"
                        x-cloak
                        class="border-t border-slate-200 pt-4 -mx-4 px-4 bg-slate-50/90 -mb-4 pb-4 rounded-b-xl"
                    >
                        @include('admin.report.details', ['subscription' => $subscription, 'trafficMaps' => $trafficMaps, 'byteFmt' => $byteFmt])
                    </div>
                    <div class="px-4 pb-4 pt-2 border-t border-slate-200">
                        <form
                            method="post"
                            action="{{ route('admin.subscription.destroy', $subscription) }}"
                            onsubmit="return confirm('Удалить подписку #{{ $subscription->id }}? Клиенты в панелях FI/NL и запись в БД будут удалены.');"
                        >
                            @csrf
                            <button type="submit" class="w-full rounded-xl border border-rose-200 bg-rose-50 py-3 text-sm font-bold text-rose-900 hover:bg-rose-100 min-h-[48px]">
                                Удалить подписку
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/80 px-6 py-14 text-center text-slate-600">
                Нет записей за выбранный период.
            </div>
        @endforelse
    </div>

    {{-- Десктоп: таблица --}}
    <article class="hidden lg:block rounded-3xl border-2 border-slate-200/90 bg-white shadow-xl shadow-slate-300/25 overflow-hidden">
        @if ($subscriptions->isEmpty())
            <p class="px-6 py-16 text-center text-slate-500 text-base bg-slate-50/50">Нет записей за выбранный период.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse min-w-[56rem]">
                    <thead>
                        <tr class="bg-slate-900 text-white">
                            <th class="w-12 px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90" scope="col"></th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90" scope="col">ID</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Старт</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Окончание</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90" scope="col">Статус</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Квота</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap" scope="col">Трафик</th>
                            <th class="px-4 py-4 font-bold text-[11px] uppercase tracking-[0.12em] text-white/90 whitespace-nowrap text-right" scope="col">Действия</th>
                        </tr>
                    </thead>
                    @foreach ($subscriptions as $subscription)
                        @php
                            $order = config('xui.bundle_order', ['fi', 'nl']);
                            $sums = [];
                            foreach ($order as $bundleKey) {
                                $col = $bundleKey.'_sub_id';
                                $sid = $subscription->{$col} ?? null;
                                if (! is_string($sid) || $sid === '') {
                                    continue;
                                }
                                $t = ($trafficMaps[$bundleKey] ?? [])[$sid] ?? null;
                                if ($t) {
                                    $sums[] = $t['up'] + $t['down'];
                                }
                            }
                            $totalUsed = $sums === [] ? null : array_sum($sums);
                            $exp = $subscription->expiresAt();
                        @endphp
                        <tbody
                            class="border-b border-slate-200 last:border-b-0"
                            x-data="{ open: false }"
                        >
                            <tr class="{{ $loop->iteration % 2 === 0 ? 'bg-slate-50/50' : 'bg-white' }} hover:bg-slate-100/80 transition-colors">
                                <td class="px-4 py-3 align-middle">
                                    <button
                                        type="button"
                                        class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-200 hover:text-slate-900 transition-colors"
                                        @click="open = !open"
                                        :aria-expanded="open"
                                    >
                                        <span x-show="! open" class="text-lg leading-none" aria-hidden="true">▸</span>
                                        <span x-show="open" x-cloak class="text-lg leading-none" aria-hidden="true">▾</span>
                                    </button>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-800 tabular-nums align-middle font-semibold">{{ $subscription->id }}</td>
                                <td class="px-4 py-3 text-slate-800 tabular-nums align-middle whitespace-nowrap">{{ $subscription->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-800 tabular-nums align-middle whitespace-nowrap">{{ $exp ? $exp->timezone(config('app.timezone'))->format('d.m.Y H:i') : '—' }}</td>
                                <td class="px-4 py-3 align-middle">
                                    @if ($exp === null)
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-slate-200/80 text-slate-700">—</span>
                                    @elseif ($subscription->isExpired())
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 ring-1 ring-rose-200/80">Истекла</span>
                                    @else
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200/80">Активна</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-900 font-semibold tabular-nums align-middle whitespace-nowrap">{{ $subscription->quota_gb }} ГБ</td>
                                <td class="px-4 py-3 text-slate-900 font-semibold tabular-nums align-middle whitespace-nowrap">{{ $totalUsed !== null ? $byteFmt($totalUsed) : '—' }}</td>
                                <td class="px-4 py-3 align-middle text-right whitespace-nowrap">
                                    <form
                                        method="post"
                                        action="{{ route('admin.subscription.destroy', $subscription) }}"
                                        class="inline"
                                        onsubmit="return confirm('Удалить подписку #{{ $subscription->id }}? Клиенты в панелях FI/NL и запись в БД будут удалены.');"
                                    >
                                        @csrf
                                        <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-900 hover:bg-rose-100">
                                            Удалить
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <tr x-show="open" x-cloak class="bg-gradient-to-br from-slate-50 to-slate-100/90">
                                <td colspan="8" class="px-5 py-5 border-t border-slate-200/80">
                                    @include('admin.report.details', ['subscription' => $subscription, 'trafficMaps' => $trafficMaps, 'byteFmt' => $byteFmt])
                                </td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>
        @endif
    </article>

    @if ($subscriptions->hasPages())
        <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-2 sm:px-4 py-3 sm:py-4 overflow-x-auto shadow-sm ring-1 ring-slate-900/5">
            <div class="flex justify-center lg:justify-start min-w-max sm:min-w-0">
                {{ $subscriptions->links() }}
            </div>
        </div>
    @endif
@endsection

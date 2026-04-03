@extends('layouts.admin')

@section('title', '')

@section('content')
    <style>[x-cloak]{display:none!important}</style>
    <a href="{{ route('admin.dashboard') }}" class="inline-block text-slate-600 hover:text-slate-900 mb-8 text-lg font-medium">
        ←
    </a>

    @if (! empty($trafficErrors))
        <div class="mb-6 rounded-2xl border border-amber-200/90 bg-amber-50 px-5 py-4 text-amber-950 text-sm shadow-sm ring-1 ring-amber-900/5 space-y-2">
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
        class="mb-8 flex flex-wrap items-end gap-4 rounded-2xl border border-slate-200/90 bg-white px-5 py-4 shadow-md shadow-slate-200/40 ring-1 ring-slate-900/5"
    >
        <div>
            <label for="date_from" class="block text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 mb-2">С даты</label>
            <input
                type="date"
                name="date_from"
                id="date_from"
                value="{{ $dateFrom }}"
                class="rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400"
            >
        </div>
        <div>
            <label for="date_to" class="block text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 mb-2">По дату</label>
            <input
                type="date"
                name="date_to"
                id="date_to"
                value="{{ $dateTo }}"
                class="rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400"
            >
        </div>
        <button type="submit" class="rounded-xl bg-slate-900 text-white px-5 py-2.5 text-sm font-bold shadow-sm hover:bg-slate-800 transition-colors">
            Показать
        </button>
        @if ($dateFrom !== '' || $dateTo !== '')
            <a href="{{ route('admin.report') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 py-2.5">Сбросить</a>
        @endif
    </form>

    <article class="rounded-3xl border-2 border-slate-200/90 bg-white shadow-xl shadow-slate-300/25 overflow-hidden">
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
                    </tr>
                </thead>
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
                        </tr>
                        <tr x-show="open" x-cloak class="bg-gradient-to-br from-slate-50 to-slate-100/90">
                            <td colspan="7" class="px-5 py-5 border-t border-slate-200/80">
                                <div class="text-sm text-slate-700 space-y-4 max-w-4xl">
                                    <p class="flex flex-wrap gap-x-2 gap-y-1 items-baseline">
                                        <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-500">Устройства</span>
                                        <span class="font-semibold text-slate-900">{{ $subscription->devices }}</span>
                                    </p>
                                    <div class="grid sm:grid-cols-2 gap-3">
                                        @foreach (config('xui.bundle_order', ['fi', 'nl']) as $bundleKey)
                                            @php
                                                $col = $bundleKey.'_sub_id';
                                                $sid = $subscription->{$col} ?? '';
                                                $t = is_string($sid) && $sid !== '' ? (($trafficMaps[$bundleKey] ?? [])[$sid] ?? null) : null;
                                                $node = config('xui.nodes.'.$bundleKey, []);
                                                $label = is_array($node) ? (string) ($node['vless_display_name'] ?? strtoupper($bundleKey)) : strtoupper($bundleKey);
                                            @endphp
                                            @if (is_string($sid) && $sid !== '')
                                                <div class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-900/5">
                                                    <div class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-500 mb-2">{{ $label }}</div>
                                                    <p class="font-mono text-xs break-all text-slate-500 mb-2 leading-relaxed">subId: {{ $sid }}</p>
                                                    <p class="text-slate-900 font-medium tabular-nums">
                                                        @if ($t)
                                                            ↑ {{ $byteFmt($t['up']) }} · ↓ {{ $byteFmt($t['down']) }} · Σ {{ $byteFmt($t['up'] + $t['down']) }}
                                                        @else
                                                            —
                                                        @endif
                                                    </p>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                    <p class="text-xs text-slate-500 break-all pt-1 border-t border-slate-200/60 font-mono">
                                        {{ rtrim(config('app.url'), '/') }}/sub/{{ $subscription->token }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center text-slate-500 text-base bg-slate-50/50">
                                Нет записей за выбранный период.
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>
        @if ($subscriptions->hasPages())
            <div class="border-t border-slate-200 bg-slate-50/80 px-5 py-4">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </article>
@endsection

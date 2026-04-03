@extends('layouts.admin')

@section('title', 'Отчёт')

@section('content')
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Отчёт по подпискам</h1>
        <p class="mt-2 text-slate-600 text-sm sm:text-base max-w-3xl">
            Список выданных подписок из базы хаба и фактический трафик с панелей 3x-ui (кэш {{ (int) config('xui.report_traffic_cache_ttl', 60) }} с).
            Позже сюда же можно добавить историю событий и привязку к логину пользователя сайта.
        </p>
    </div>

    @if (! empty($trafficErrors))
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-950 text-sm space-y-1">
            <p class="font-semibold">Трафик с панелей частично или полностью недоступен:</p>
            <ul class="list-disc list-inside">
                @foreach ($trafficErrors as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="get"
        action="{{ route('admin.report') }}"
        class="mb-8 flex flex-wrap items-end gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm"
    >
        <div>
            <label for="date_from" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">С даты</label>
            <input
                type="date"
                name="date_from"
                id="date_from"
                value="{{ $dateFrom }}"
                class="rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400"
            >
        </div>
        <div>
            <label for="date_to" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">По дату</label>
            <input
                type="date"
                name="date_to"
                id="date_to"
                value="{{ $dateTo }}"
                class="rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400"
            >
        </div>
        <button type="submit" class="rounded-xl bg-slate-900 text-white px-5 py-2.5 text-sm font-semibold hover:bg-slate-800">
            Показать
        </button>
        @if ($dateFrom !== '' || $dateTo !== '')
            <a href="{{ route('admin.report') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 py-2.5">Сбросить</a>
        @endif
    </form>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="hidden lg:grid lg:grid-cols-[2rem_4rem_1fr_1fr_7rem_5rem_8rem] gap-0 bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500 px-4 py-3">
            <span></span>
            <span>ID</span>
            <span>Старт</span>
            <span>Окончание</span>
            <span>Статус</span>
            <span>Квота</span>
            <span>Трафик</span>
        </div>

        <div class="divide-y divide-slate-100">
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
                <details class="group border-slate-100">
                    <summary class="list-none cursor-pointer grid lg:grid-cols-[2rem_4rem_1fr_1fr_7rem_5rem_8rem] gap-x-2 gap-y-1 items-center px-4 py-3 hover:bg-slate-50/80 [&::-webkit-details-marker]:hidden">
                        <span class="text-slate-400 group-open:rotate-90 transition-transform w-6 text-center inline-block" aria-hidden="true">▸</span>
                        <span class="font-mono text-xs text-slate-700 tabular-nums">{{ $subscription->id }}</span>
                        <span class="text-slate-800 text-sm">{{ $subscription->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}</span>
                        <span class="text-slate-800 text-sm">{{ $exp ? $exp->timezone(config('app.timezone'))->format('d.m.Y H:i') : '—' }}</span>
                        <span>
                            @if ($exp === null)
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">—</span>
                            @elseif ($subscription->isExpired())
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">Истекла</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Активна</span>
                            @endif
                        </span>
                        <span class="text-slate-800 text-sm tabular-nums">{{ $subscription->quota_gb }} ГБ</span>
                        <span class="text-slate-800 text-sm tabular-nums">{{ $totalUsed !== null ? $byteFmt($totalUsed) : '—' }}</span>
                    </summary>
                    <div class="px-4 pb-4 pt-0 pl-10 sm:pl-14 border-t border-slate-100 bg-slate-50/50 text-sm text-slate-600 space-y-3">
                        <p><span class="font-semibold text-slate-700">Устройства (limitIp):</span> {{ $subscription->devices }}</p>
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
                                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                                        <div class="text-xs font-bold uppercase text-slate-500 mb-1">Узел {{ $label }}</div>
                                        <p class="font-mono text-xs break-all text-slate-500 mb-1">subId: {{ $sid }}</p>
                                        <p class="text-slate-800">
                                            @if ($t)
                                                ↑ {{ $byteFmt($t['up']) }} · ↓ {{ $byteFmt($t['down']) }} · всего {{ $byteFmt($t['up'] + $t['down']) }}
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        <p class="text-xs text-slate-500 break-all">
                            <span class="font-semibold text-slate-600">Ссылка подписки:</span>
                            {{ rtrim(config('app.url'), '/') }}/sub/{{ $subscription->token }}
                        </p>
                    </div>
                </details>
            @empty
                <p class="px-4 py-12 text-center text-slate-500">Нет записей за выбранный период.</p>
            @endforelse
        </div>
        @if ($subscriptions->hasPages())
            <div class="border-t border-slate-200 px-4 py-3 bg-slate-50/50">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>
@endsection

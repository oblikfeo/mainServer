@extends('layouts.admin')

@section('title', '')

@php
    $tile = static function (?string $level): string {
        return match ($level) {
            'ok' => 'from-emerald-50 to-emerald-100 border-emerald-200/80 shadow-sm',
            'warn' => 'from-amber-50 to-amber-100 border-amber-200/80 shadow-sm',
            'crit' => 'from-rose-50 to-rose-100 border-rose-200/80 shadow-sm',
            default => 'from-slate-50 to-slate-100 border-slate-200/80 shadow-sm',
        };
    };
@endphp

@section('content')
    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← В меню
    </a>

    <ul class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-6 sm:mb-10 list-none p-0">
        <li class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50 p-4 sm:p-6 shadow-md shadow-slate-200/40">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Подписок активных</div>
            <div class="mt-2 sm:mt-3 text-3xl sm:text-4xl font-bold tabular-nums text-slate-900 tracking-tight">{{ $totalActiveSubs }}</div>
        </li>
        <li class="rounded-2xl border border-emerald-200/70 bg-gradient-to-br from-emerald-50 to-white p-4 sm:p-6 shadow-md shadow-emerald-200/30">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-emerald-700/80">Узлы</div>
            <div class="mt-2 sm:mt-3 text-3xl sm:text-4xl font-bold tabular-nums text-emerald-700 tracking-tight">{{ $onlineCount }}<span class="text-emerald-600/70 font-bold">/{{ $totalBundles }}</span></div>
        </li>
        <li class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50 p-4 sm:p-6 shadow-md shadow-slate-200/40">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Соединений (все узлы)</div>
            <div class="mt-2 sm:mt-3 text-3xl sm:text-4xl font-bold tabular-nums text-slate-900 tracking-tight">{{ $totalConnections }}</div>
        </li>
    </ul>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 sm:gap-8">
        @foreach ($bundles as $bundle)
            @php
                $m = $bundle['metrics'] ?? null;
                $ctMax = $m ? (int) ($m['conntrack_max'] ?? 0) : 0;
            @endphp
            <article
                class="rounded-3xl border-2 overflow-hidden flex flex-col min-h-0
                    {{ $bundle['online']
                        ? 'border-slate-200/90 bg-white shadow-xl shadow-slate-300/25'
                        : 'border-rose-200 bg-rose-50/50 shadow-lg shadow-rose-200/20' }}"
            >
                <header class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 px-6 py-5 {{ $bundle['online'] ? 'bg-slate-900 text-white' : 'bg-rose-900 text-white' }}">
                    <div class="min-w-0">
                        <h2 class="text-xl sm:text-2xl font-bold tracking-tight">
                            {{ $bundle['name'] }}
                        </h2>
                        @if (! empty($bundle['subtitle']))
                            <p class="text-sm text-white/75 mt-1">{{ $bundle['subtitle'] }}</p>
                        @endif
                    </div>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider shrink-0 {{ $bundle['online'] ? 'bg-white/15 text-white ring-1 ring-white/25' : 'bg-white/20 text-white ring-1 ring-white/30' }}">
                        {{ $bundle['online'] ? 'Онлайн' : 'Офлайн' }}
                    </span>
                </header>

                <div class="p-5 grid grid-cols-2 gap-3 flex-1 bg-slate-50/80">
                    <div class="rounded-2xl border bg-gradient-to-br p-4 flex flex-col justify-between min-h-[6.75rem] ring-1 ring-inset ring-white/70 shadow-sm {{ $tile($bundle['subs_level'] ?? null) }}">
                        <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-600">Подписок</span>
                        <span class="text-2xl sm:text-3xl font-bold tabular-nums text-slate-900 mt-2">{{ $bundle['subs_count'] }}</span>
                    </div>
                    <div class="rounded-2xl border bg-gradient-to-br p-4 flex flex-col justify-between min-h-[6.75rem] ring-1 ring-inset ring-white/70 shadow-sm {{ $tile(null) }}">
                        <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-600">Активные IP</span>
                        <span class="text-2xl sm:text-3xl font-bold tabular-nums text-slate-900 mt-2">{{ $m ? (int) ($m['unique_remote_ips'] ?? 0) : '—' }}</span>
                        <span class="text-[10px] text-slate-500 mt-1 leading-tight">Уникальные исходящие IP на порту {{ (int) ($bundle['client_tcp_port'] ?? 443) }} (SSH)</span>
                    </div>
                    <div class="rounded-2xl border bg-gradient-to-br p-4 flex flex-col justify-between min-h-[6.75rem] ring-1 ring-inset ring-white/70 shadow-sm {{ $m ? $tile($m['cpu_level'] ?? null) : $tile(null) }}">
                        <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-600">CPU</span>
                        <span class="text-2xl sm:text-3xl font-bold tabular-nums text-slate-900 mt-2">{{ $m ? $m['cpu_util_pct'].'%' : '—' }}</span>
                    </div>
                    <div class="rounded-2xl border bg-gradient-to-br p-4 flex flex-col justify-between min-h-[6.75rem] ring-1 ring-inset ring-white/70 shadow-sm {{ $m ? $tile($m['ram_level'] ?? null) : $tile(null) }}">
                        <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-600">RAM</span>
                        <span class="text-lg sm:text-2xl font-bold tabular-nums text-slate-900 mt-2 leading-snug">
                            @if ($m)
                                {{ $m['mem_used_gb'] }} / {{ $m['mem_total_gb'] }} <span class="text-base font-semibold text-slate-600">ГБ</span>
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div class="col-span-2 rounded-2xl border bg-gradient-to-br p-4 flex flex-col justify-between min-h-[6.75rem] ring-1 ring-inset ring-white/70 shadow-sm {{ $m && $ctMax > 0 ? $tile($m['conntrack_level'] ?? null) : $tile(null) }}">
                        <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-600">NAT</span>
                        <span class="text-base sm:text-lg font-bold tabular-nums text-slate-900 mt-2 leading-tight break-words">
                            @if ($m && $ctMax > 0)
                                {{ number_format((int) $m['conntrack_used'], 0, '.', ' ') }}<span class="text-slate-500 font-semibold"> / </span>{{ number_format($ctMax, 0, '.', ' ') }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    <div class="col-span-2 rounded-2xl border bg-gradient-to-br p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 min-h-[5.5rem] ring-1 ring-inset ring-white/70 shadow-sm {{ $m ? $tile($bundle['traffic_level'] ?? null) : $tile(null) }}">
                        <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-600 shrink-0">Трафик</span>
                        <span class="text-2xl sm:text-3xl font-bold tabular-nums text-slate-900">
                            @if ($m)
                                @php
                                    $b = (int) $m['traffic_total_bytes'];
                                    $tb = $b / 1_000_000_000_000;
                                    $gb = $b / 1_000_000_000;
                                @endphp
                                {{ number_format($tb, 3, '.', ' ') }} <span class="text-lg font-semibold text-slate-600">ТБ</span>
                                <span class="block sm:inline sm:ml-2 text-sm font-semibold text-slate-500">({{ number_format($gb, 1, '.', ' ') }} ГБ)</span>
                            @else
                                —
                            @endif
                        </span>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endsection

@extends('layouts.admin')

@section('title', '')

@php
    $row = static function (?string $level): string {
        return match ($level) {
            'ok' => 'border-l-emerald-500 bg-emerald-50/60',
            'warn' => 'border-l-amber-500 bg-amber-50/55',
            'crit' => 'border-l-rose-600 bg-rose-50/65',
            default => 'border-l-slate-200 bg-slate-50/90',
        };
    };
@endphp

@section('content')
    <a href="{{ route('admin.dashboard') }}" class="inline-block text-slate-600 hover:text-slate-900 mb-8 text-lg font-medium">
        ←
    </a>

    <ul class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10 list-none p-0">
        <li class="rounded-xl border border-slate-200/90 bg-white px-6 py-5 shadow-sm ring-1 ring-slate-900/5">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Ключей выдано</div>
            <div class="mt-2 text-3xl sm:text-4xl font-bold tabular-nums text-slate-900 leading-none">{{ $totalKeys }}</div>
        </li>
        <li class="rounded-xl border border-slate-200/90 bg-white px-6 py-5 shadow-sm ring-1 ring-slate-900/5">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Узлов онлайн</div>
            <div class="mt-2 text-3xl sm:text-4xl font-bold tabular-nums text-emerald-600 leading-none">{{ $onlineCount }}</div>
        </li>
        <li class="rounded-xl border border-slate-200/90 bg-white px-6 py-5 shadow-sm ring-1 ring-slate-900/5">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Узлов всего</div>
            <div class="mt-2 text-3xl sm:text-4xl font-bold tabular-nums text-slate-800 leading-none">{{ $totalBundles }}</div>
        </li>
    </ul>

    <ol class="space-y-6 list-none p-0 m-0">
        @foreach ($bundles as $bundle)
            @php
                $m = $bundle['metrics'] ?? null;
                $ctMax = $m ? (int) ($m['conntrack_max'] ?? 0) : 0;
            @endphp
            <li>
                <article
                    class="rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/5 overflow-hidden
                        {{ $bundle['online'] ? '' : 'ring-rose-200/80 border-rose-200' }}"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4 border-b border-slate-100
                            {{ $bundle['online'] ? 'bg-gradient-to-b from-slate-50 to-white' : 'bg-rose-50/80' }}"
                    >
                        <div class="min-w-0">
                            <h2 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">
                                {{ $bundle['name'] }}
                            </h2>
                            @if (! empty($bundle['subtitle']))
                                <p class="text-sm text-slate-500 mt-0.5">{{ $bundle['subtitle'] }}</p>
                            @endif
                        </div>
                        <span
                            class="inline-flex items-center self-start sm:self-center px-3 py-1 rounded-md text-xs font-semibold uppercase tracking-wide shrink-0
                                {{ $bundle['online'] ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white' }}"
                        >
                            {{ $bundle['online'] ? 'Онлайн' : 'Офлайн' }}
                        </span>
                    </div>

                    <div class="p-0">
                        <div class="divide-y divide-slate-100">
                            <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto] gap-x-8 gap-y-1 items-center min-h-[3.25rem] py-3 px-5 border-l-4 {{ $row($bundle['keys_level'] ?? null) }}">
                                <span class="text-sm font-medium text-slate-600">Ключи</span>
                                <span class="text-base sm:text-lg font-semibold tabular-nums text-slate-900 text-left sm:text-right">{{ $bundle['keys_count'] }}</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto] gap-x-8 gap-y-1 items-center min-h-[3.25rem] py-3 px-5 border-l-4 {{ $m ? $row($m['cpu_level'] ?? null) : $row(null) }}">
                                <span class="text-sm font-medium text-slate-600">CPU</span>
                                <span class="text-base sm:text-lg font-semibold tabular-nums text-slate-900 text-left sm:text-right">{{ $m ? $m['cpu_util_pct'].'%' : '—' }}</span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto] gap-x-8 gap-y-1 items-center min-h-[3.25rem] py-3 px-5 border-l-4 {{ $m ? $row($m['ram_level'] ?? null) : $row(null) }}">
                                <span class="text-sm font-medium text-slate-600">RAM</span>
                                <span class="text-base sm:text-lg font-semibold tabular-nums text-slate-900 text-left sm:text-right">
                                    @if ($m)
                                        {{ $m['mem_used_gb'] }} / {{ $m['mem_total_gb'] }} ГБ
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto] gap-x-8 gap-y-1 items-center min-h-[3.25rem] py-3 px-5 border-l-4 {{ $m && $ctMax > 0 ? $row($m['conntrack_level'] ?? null) : $row(null) }}">
                                <span class="text-sm font-medium text-slate-600">NAT-сессии</span>
                                <span class="text-base sm:text-lg font-semibold tabular-nums text-slate-900 text-left sm:text-right break-all sm:break-normal">
                                    @if ($m && $ctMax > 0)
                                        {{ number_format((int) $m['conntrack_used'], 0, '.', ' ') }} / {{ number_format($ctMax, 0, '.', ' ') }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_auto] gap-x-8 gap-y-1 items-center min-h-[3.25rem] py-3 px-5 border-l-4 {{ $m ? $row($bundle['traffic_level'] ?? null) : $row(null) }}">
                                <span class="text-sm font-medium text-slate-600">Трафик</span>
                                <span class="text-base sm:text-lg font-semibold tabular-nums text-slate-900 text-left sm:text-right">
                                    @if ($m)
                                        @php
                                            $b = (int) $m['traffic_total_bytes'];
                                            $tb = $b / 1_000_000_000_000;
                                        @endphp
                                        {{ number_format($tb, 2, '.', ' ') }} ТБ
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </article>
            </li>
        @endforeach
    </ol>
@endsection

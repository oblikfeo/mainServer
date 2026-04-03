@extends('layouts.admin')

@section('title', '')

@php
    $fill = static function (?string $level): string {
        return match ($level) {
            'ok' => 'bg-emerald-300',
            'warn' => 'bg-amber-300',
            'crit' => 'bg-rose-400',
            default => 'bg-stone-300',
        };
    };
@endphp

@section('content')
    <a href="{{ route('admin.dashboard') }}" class="inline-block text-slate-600 hover:text-slate-900 mb-8 text-lg font-medium">
        ←
    </a>

    <ul class="flex flex-col sm:flex-row sm:flex-wrap gap-3 mb-10 list-none p-0">
        <li class="flex-1 min-w-[10rem] rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <div class="text-3xl sm:text-4xl font-bold tabular-nums text-slate-900">{{ $totalKeys }}</div>
            <div class="text-slate-500 text-sm font-medium mt-0.5">ключей выдано</div>
        </li>
        <li class="flex-1 min-w-[10rem] rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <div class="text-3xl sm:text-4xl font-bold tabular-nums text-emerald-600">{{ $onlineCount }}</div>
            <div class="text-slate-500 text-sm font-medium mt-0.5">узлов онлайн</div>
        </li>
        <li class="flex-1 min-w-[10rem] rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <div class="text-3xl sm:text-4xl font-bold tabular-nums text-slate-700">{{ $totalBundles }}</div>
            <div class="text-slate-500 text-sm font-medium mt-0.5">узлов всего</div>
        </li>
    </ul>

    <ol class="space-y-4 list-none p-0 m-0">
        @foreach ($bundles as $bundle)
            @php
                $m = $bundle['metrics'] ?? null;
                $ctMax = $m ? (int) ($m['conntrack_max'] ?? 0) : 0;
            @endphp
            <li>
                <article
                    class="rounded-2xl border overflow-hidden shadow-sm
                        {{ $bundle['online'] ? 'border-slate-200 bg-white' : 'border-rose-200 bg-rose-50/40' }}"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-4 border-b
                            {{ $bundle['online'] ? 'border-slate-100 bg-slate-50/70' : 'border-rose-100/80 bg-rose-100/30' }}"
                    >
                        <div class="min-w-0">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                                {{ $bundle['name'] }}
                            </h2>
                            @if (! empty($bundle['subtitle']))
                                <p class="text-sm text-slate-500 mt-0.5">{{ $bundle['subtitle'] }}</p>
                            @endif
                        </div>
                        <span
                            class="inline-flex items-center self-start sm:self-center px-3 py-1.5 rounded-lg text-sm font-semibold shrink-0 tabular-nums
                                {{ $bundle['online'] ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-200 text-rose-900' }}"
                        >
                            {{ $bundle['online'] ? 'онлайн' : 'офлайн' }}
                        </span>
                    </div>

                    <div class="p-4 sm:p-5">
                        <ul class="space-y-1 text-slate-900">
                            <li class="flex justify-between items-center gap-4 px-4 py-3.5 rounded-lg {{ $fill($bundle['keys_level'] ?? null) }}">
                                <span class="font-medium">Ключи</span>
                                <span class="text-xl font-bold tabular-nums">{{ $bundle['keys_count'] }}</span>
                            </li>
                            <li class="flex justify-between items-center gap-4 px-4 py-3.5 rounded-lg {{ $m ? $fill($m['cpu_level'] ?? null) : $fill(null) }}">
                                <span class="font-medium">CPU</span>
                                <span class="text-xl font-bold tabular-nums">{{ $m ? $m['cpu_util_pct'].'%' : '—' }}</span>
                            </li>
                            <li class="flex justify-between items-center gap-4 px-4 py-3.5 rounded-lg {{ $m ? $fill($m['ram_level'] ?? null) : $fill(null) }}">
                                <span class="font-medium">RAM</span>
                                <span class="text-xl font-bold tabular-nums">
                                    @if ($m)
                                        {{ $m['mem_used_gb'] }} / {{ $m['mem_total_gb'] }} ГБ
                                    @else
                                        —
                                    @endif
                                </span>
                            </li>
                            <li class="flex justify-between items-center gap-4 px-4 py-3.5 rounded-lg {{ $m && $ctMax > 0 ? $fill($m['conntrack_level'] ?? null) : $fill(null) }}">
                                <span class="font-medium">NAT-сессии</span>
                                <span class="text-xl font-bold tabular-nums">
                                    @if ($m && $ctMax > 0)
                                        {{ number_format((int) $m['conntrack_used'], 0, '.', ' ') }} / {{ number_format($ctMax, 0, '.', ' ') }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </li>
                            <li class="flex justify-between items-center gap-4 px-4 py-3.5 rounded-lg {{ $m ? $fill($bundle['traffic_level'] ?? null) : $fill(null) }}">
                                <span class="font-medium">Трафик</span>
                                <span class="text-xl font-bold tabular-nums">
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
                            </li>
                        </ul>
                    </div>
                </article>
            </li>
        @endforeach
    </ol>
@endsection

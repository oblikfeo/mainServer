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

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10">
        <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
            <div class="text-4xl sm:text-5xl font-bold tabular-nums text-slate-900">{{ $totalKeys }}</div>
            <div class="text-slate-500 text-sm font-medium mt-1">ключей выдано</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
            <div class="text-4xl sm:text-5xl font-bold tabular-nums text-emerald-600">{{ $onlineCount }}</div>
            <div class="text-slate-500 text-sm font-medium mt-1">узлов онлайн</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
            <div class="text-4xl sm:text-5xl font-bold tabular-nums text-slate-700">{{ $totalBundles }}</div>
            <div class="text-slate-500 text-sm font-medium mt-1">узлов всего</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach ($bundles as $bundle)
            @php
                $m = $bundle['metrics'] ?? null;
            @endphp
            <article
                class="rounded-2xl border-2 p-8 shadow-md
                    {{ $bundle['online'] ? 'border-emerald-200 bg-white' : 'border-rose-300 bg-rose-50/50' }}"
            >
                <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-3xl sm:text-4xl font-bold text-slate-900">
                            {{ $bundle['name'] }}
                        </h2>
                        @if (!empty($bundle['subtitle']))
                            <p class="mt-1 text-lg text-slate-600">{{ $bundle['subtitle'] }}</p>
                        @endif
                    </div>
                    <span
                        class="inline-flex items-center px-4 py-2 rounded-xl text-base font-semibold shrink-0
                            {{ $bundle['online'] ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}"
                    >
                        {{ $bundle['online'] ? 'онлайн' : 'офлайн' }}
                    </span>
                </div>

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
            </article>
        @endforeach
    </div>
@endsection

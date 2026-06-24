@extends('layouts.admin')

@section('title', 'Что работает?')

@php
    $statusBadge = static function (?string $status): string {
        return match ($status) {
            'ok' => 'bg-emerald-100 text-emerald-800 ring-emerald-200/80',
            'warn' => 'bg-amber-100 text-amber-900 ring-amber-200/80',
            'fail' => 'bg-rose-100 text-rose-800 ring-rose-200/80',
            'skip' => 'bg-slate-100 text-slate-600 ring-slate-200/80',
            default => 'bg-slate-100 text-slate-600 ring-slate-200/80',
        };
    };

    $statusLabel = static function (?string $status): string {
        return match ($status) {
            'ok' => 'OK',
            'warn' => 'Частично',
            'fail' => 'FAIL',
            'skip' => '—',
            default => '—',
        };
    };

    $rowBorder = static function (?string $status): string {
        return match ($status) {
            'ok' => 'border-l-emerald-500',
            'warn' => 'border-l-amber-500',
            'fail' => 'border-l-rose-500',
            default => 'border-l-slate-300',
        };
    };

    $formatCheckedAt = static function (?string $iso): string {
        if ($iso === null || $iso === '') {
            return '—';
        }
        try {
            return \Illuminate\Support\Carbon::parse($iso)->timezone(config('app.timezone'))->format('d.m.Y H:i');
        } catch (\Throwable) {
            return $iso;
        }
    };

    $rows = is_array($results['rows'] ?? null) ? $results['rows'] : [];
    $vpnRows = array_values(array_filter($rows, fn ($r) => is_array($r) && ($r['kind'] ?? '') === 'vpn'));
    $webRows = array_values(array_filter($rows, fn ($r) => is_array($r) && ($r['kind'] ?? '') === 'web'));
    $vpnOk = count(array_filter($vpnRows, fn ($r) => ($r['status'] ?? '') === 'ok'));
    $webOk = count(array_filter($webRows, fn ($r) => ($r['status'] ?? '') === 'ok'));
@endphp

@section('content')
    <div class="max-w-5xl mx-auto w-full min-w-0">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="min-w-0">
                <a
                    href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 mb-4"
                >
                    ← В меню
                </a>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">Что работает?</h1>
                <p class="mt-1 text-sm text-slate-500">
                    VPN {{ $vpnOk }}/{{ count($vpnRows) }} · сайты {{ $webOk }}/{{ count($webRows) }}
                    · {{ $formatCheckedAt($results['checked_at'] ?? null) }}
                    · кэш {{ (int) $cacheTtl }} с
                </p>
            </div>

            <form method="POST" action="{{ route('admin.what_works.run') }}" class="shrink-0 w-full sm:w-auto">
                @csrf
                <button
                    type="submit"
                    class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 min-h-[44px]"
                >
                    Проверить сейчас
                </button>
            </form>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('status') }}
            </div>
        @endif

        @if ($rows === [])
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                Нет данных для проверки.
            </div>
        @else
            <div class="space-y-8 min-w-0">
                @if ($vpnRows !== [])
                    <section class="min-w-0">
                        <h2 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500 mb-3">VPN · Happ</h2>
                        <ul class="space-y-3 list-none p-0 m-0">
                            @foreach ($vpnRows as $row)
                                @php $status = (string) ($row['status'] ?? 'fail'); @endphp
                                <li class="rounded-2xl border border-slate-200/90 bg-white shadow-sm border-l-[3px] {{ $rowBorder($status) }} overflow-hidden min-w-0">
                                    <div class="flex items-start justify-between gap-3 p-4 sm:p-5 min-w-0">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-semibold text-slate-900 truncate">{{ $row['title'] ?? $row['id'] }}</div>
                                            <div class="text-[11px] uppercase tracking-wide text-slate-400 mt-0.5">{{ $row['id'] ?? '' }}</div>
                                        </div>
                                        <span class="shrink-0 inline-flex rounded-lg px-2.5 py-1 text-xs font-bold ring-1 {{ $statusBadge($status) }}">
                                            {{ $statusLabel($status) }}
                                        </span>
                                    </div>
                                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-slate-100 border-t border-slate-100 text-sm">
                                        <div class="bg-white px-4 py-3 min-w-0">
                                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">ms</dt>
                                            <dd class="mt-0.5 font-semibold tabular-nums text-slate-900">{{ isset($row['latency_ms']) ? (int) $row['latency_ms'] : '—' }}</dd>
                                        </div>
                                        <div class="bg-white px-4 py-3 min-w-0">
                                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">↓ Mbps</dt>
                                            <dd class="mt-0.5 font-semibold tabular-nums text-slate-900">{{ isset($row['download_mbps']) ? number_format((float) $row['download_mbps'], 1, '.', '') : '—' }}</dd>
                                        </div>
                                        <div class="bg-white px-4 py-3 min-w-0 col-span-2 sm:col-span-2">
                                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Egress IP</dt>
                                            <dd class="mt-0.5 font-mono text-xs sm:text-sm text-slate-800 truncate" title="{{ $row['egress_ip'] ?? '' }}">{{ $row['egress_ip'] ?? '—' }}</dd>
                                        </div>
                                    </dl>
                                    @if (! empty($row['error']))
                                        <p class="px-4 py-2.5 text-xs text-rose-700 bg-rose-50/80 border-t border-rose-100 break-words">{{ $row['error'] }}</p>
                                    @elseif (! empty($row['egress_colo']))
                                        <p class="px-4 py-2 text-xs text-slate-400 border-t border-slate-100">{{ $row['egress_colo'] }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($webRows !== [])
                    <section class="min-w-0">
                        <h2 class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500 mb-3">Сайты в сети</h2>
                        <ul class="space-y-3 list-none p-0 m-0">
                            @foreach ($webRows as $row)
                                @php $status = (string) ($row['status'] ?? 'fail'); @endphp
                                <li class="rounded-2xl border border-slate-200/90 bg-white shadow-sm border-l-[3px] {{ $rowBorder($status) }} overflow-hidden min-w-0">
                                    <div class="flex items-center justify-between gap-3 p-4 sm:p-5 min-w-0">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-semibold text-slate-900 truncate">{{ $row['title'] ?? $row['id'] }}</div>
                                            <div class="text-xs text-slate-500 mt-0.5">
                                                @if (! empty($row['error']))
                                                    <span class="text-rose-700">{{ $row['error'] }}</span>
                                                @elseif (isset($row['latency_ms']))
                                                    {{ (int) $row['latency_ms'] }} ms · открывается
                                                @else
                                                    проверка с hub
                                                @endif
                                            </div>
                                        </div>
                                        <span class="shrink-0 inline-flex rounded-lg px-2.5 py-1 text-xs font-bold ring-1 {{ $statusBadge($status) }}">
                                            {{ $statusLabel($status) }}
                                        </span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        @endif
    </div>
@endsection

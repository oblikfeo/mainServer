@extends('layouts.admin')

@section('title', 'Что работает?')

@php
    $statusStyles = static function (?string $status): array {
        return match ($status) {
            'ok' => [
                'badge' => 'bg-emerald-500/15 text-emerald-800 ring-emerald-500/30',
                'dot' => 'bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.55)]',
                'row' => 'border-l-emerald-500',
            ],
            'warn' => [
                'badge' => 'bg-amber-500/15 text-amber-900 ring-amber-500/30',
                'dot' => 'bg-amber-500 shadow-[0_0_12px_rgba(245,158,11,0.5)]',
                'row' => 'border-l-amber-500',
            ],
            'fail' => [
                'badge' => 'bg-rose-500/15 text-rose-800 ring-rose-500/30',
                'dot' => 'bg-rose-500 shadow-[0_0_12px_rgba(244,63,94,0.5)]',
                'row' => 'border-l-rose-500',
            ],
            'skip' => [
                'badge' => 'bg-slate-500/10 text-slate-600 ring-slate-300/50',
                'dot' => 'bg-slate-400',
                'row' => 'border-l-slate-300',
            ],
            default => [
                'badge' => 'bg-slate-500/10 text-slate-600 ring-slate-300/50',
                'dot' => 'bg-slate-400',
                'row' => 'border-l-slate-300',
            ],
        };
    };

    $statusLabel = static function (?string $status): string {
        return match ($status) {
            'ok' => 'Работает',
            'warn' => 'Частично',
            'fail' => 'Не работает',
            'skip' => 'Пропуск',
            default => '—',
        };
    };

    $formatCheckedAt = static function (?string $iso): string {
        if ($iso === null || $iso === '') {
            return '—';
        }
        try {
            return \Illuminate\Support\Carbon::parse($iso)->timezone(config('app.timezone'))->format('d.m.Y · H:i');
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
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5 mb-8 sm:mb-10">
        <div class="min-w-0">
            <a
                href="{{ route('admin.dashboard') }}"
                class="inline-flex items-center gap-2 text-sm sm:text-base font-semibold text-slate-500 hover:text-slate-900 transition-colors mb-4"
            >
                <span aria-hidden="true">←</span> В меню
            </a>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-slate-900">
                Что работает?
            </h1>
            <p class="mt-2 sm:mt-3 text-base sm:text-lg text-slate-600 max-w-2xl leading-relaxed">
                VPN-каналы через Happ и доступность сайтов в сети.
            </p>
        </div>

        <form method="POST" action="{{ route('admin.what_works.run') }}" class="shrink-0">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center justify-center w-full sm:w-auto rounded-2xl border-2 border-slate-900 bg-slate-900 px-6 sm:px-8 py-3.5 sm:py-4 text-base sm:text-lg font-bold text-white shadow-lg shadow-slate-900/20 hover:bg-slate-800 hover:border-slate-800 transition-all active:scale-[0.99] min-h-[52px]"
            >
                Проверить сейчас
            </button>
        </form>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-white px-5 py-4 text-base text-emerald-900 shadow-sm">
            {{ session('status') }}
        </div>
    @endif

    <ul class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5 mb-8 sm:mb-10 list-none p-0">
        <li class="rounded-2xl sm:rounded-3xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50 p-5 sm:p-6 shadow-md shadow-slate-200/50">
            <div class="text-xs sm:text-sm font-bold uppercase tracking-[0.14em] text-slate-500">VPN-узлы</div>
            <div class="mt-3 text-4xl sm:text-5xl font-bold tabular-nums text-slate-900 tracking-tight">
                {{ $vpnOk }}<span class="text-2xl sm:text-3xl text-slate-400 font-bold">/{{ count($vpnRows) }}</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">каналов в норме</div>
        </li>
        <li class="rounded-2xl sm:rounded-3xl border border-emerald-200/70 bg-gradient-to-br from-emerald-50 via-white to-emerald-50/30 p-5 sm:p-6 shadow-md shadow-emerald-200/40">
            <div class="text-xs sm:text-sm font-bold uppercase tracking-[0.14em] text-emerald-700/80">Сайты</div>
            <div class="mt-3 text-4xl sm:text-5xl font-bold tabular-nums text-emerald-700 tracking-tight">
                {{ $webOk }}<span class="text-2xl sm:text-3xl text-emerald-500/70 font-bold">/{{ count($webRows) }}</span>
            </div>
            <div class="mt-1 text-sm text-emerald-700/70">открываются</div>
        </li>
        <li class="rounded-2xl sm:rounded-3xl border border-slate-200/80 bg-gradient-to-br from-slate-900 to-slate-800 p-5 sm:p-6 shadow-lg shadow-slate-900/25 text-white">
            <div class="text-xs sm:text-sm font-bold uppercase tracking-[0.14em] text-white/60">Обновлено</div>
            <div class="mt-3 text-xl sm:text-2xl font-bold tracking-tight leading-snug">
                {{ $formatCheckedAt($results['checked_at'] ?? null) }}
            </div>
            <div class="mt-1 text-sm text-white/50">кэш {{ (int) $cacheTtl }} сек</div>
        </li>
    </ul>

    @if ($rows === [])
        <div class="rounded-3xl border border-amber-200 bg-amber-50 p-8 text-lg text-amber-900 text-center">
            Нет данных для проверки.
        </div>
    @else
        <div class="rounded-3xl border-2 border-slate-200/90 bg-white shadow-xl shadow-slate-300/20 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/90 text-left">
                            <th class="px-5 sm:px-8 py-4 sm:py-5 text-xs sm:text-sm font-bold uppercase tracking-[0.12em] text-slate-500 min-w-[12rem]">Строка</th>
                            <th class="px-4 sm:px-6 py-4 sm:py-5 text-xs sm:text-sm font-bold uppercase tracking-[0.12em] text-slate-500 min-w-[9rem]">Статус</th>
                            <th class="px-4 sm:px-6 py-4 sm:py-5 text-xs sm:text-sm font-bold uppercase tracking-[0.12em] text-slate-500 w-24 tabular-nums">ms</th>
                            <th class="px-4 sm:px-6 py-4 sm:py-5 text-xs sm:text-sm font-bold uppercase tracking-[0.12em] text-slate-500 w-28 tabular-nums">↓ Mbps</th>
                            <th class="px-4 sm:px-6 py-4 sm:py-5 text-xs sm:text-sm font-bold uppercase tracking-[0.12em] text-slate-500 min-w-[9rem]">Egress IP</th>
                            <th class="px-5 sm:px-8 py-4 sm:py-5 text-xs sm:text-sm font-bold uppercase tracking-[0.12em] text-slate-500 min-w-[10rem]">Примечание</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($vpnRows !== [])
                            <tr class="bg-slate-900/5">
                                <td colspan="6" class="px-5 sm:px-8 py-3 text-xs sm:text-sm font-bold uppercase tracking-[0.16em] text-slate-500">
                                    VPN · Happ
                                </td>
                            </tr>
                            @foreach ($vpnRows as $row)
                                @php
                                    $status = (string) ($row['status'] ?? 'fail');
                                    $style = $statusStyles($status);
                                @endphp
                                <tr class="group border-b border-slate-100 border-l-4 {{ $style['row'] }} hover:bg-slate-50/70 transition-colors">
                                    <td class="px-5 sm:px-8 py-5 sm:py-6">
                                        <div class="text-lg sm:text-xl font-bold text-slate-900 leading-tight">{{ $row['title'] ?? $row['id'] }}</div>
                                        <div class="text-xs sm:text-sm uppercase tracking-wider text-slate-400 mt-1 font-semibold">{{ $row['id'] ?? '' }}</div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-5 sm:py-6">
                                        <span class="inline-flex items-center gap-2.5 rounded-full px-3.5 py-1.5 text-sm font-bold ring-1 {{ $style['badge'] }}">
                                            <span class="h-2.5 w-2.5 rounded-full shrink-0 {{ $style['dot'] }}" aria-hidden="true"></span>
                                            {{ $statusLabel($status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-5 sm:py-6 text-xl sm:text-2xl font-bold tabular-nums text-slate-900">
                                        {{ isset($row['latency_ms']) ? (int) $row['latency_ms'] : '—' }}
                                    </td>
                                    <td class="px-4 sm:px-6 py-5 sm:py-6 text-xl sm:text-2xl font-bold tabular-nums text-slate-900">
                                        {{ isset($row['download_mbps']) ? number_format((float) $row['download_mbps'], 1, '.', '') : '—' }}
                                    </td>
                                    <td class="px-4 sm:px-6 py-5 sm:py-6">
                                        <span class="font-mono text-sm sm:text-base text-slate-800 break-all">{{ $row['egress_ip'] ?? '—' }}</span>
                                    </td>
                                    <td class="px-5 sm:px-8 py-5 sm:py-6 text-sm sm:text-base text-slate-500">
                                        @if (! empty($row['error']))
                                            <span class="text-rose-700">{{ $row['error'] }}</span>
                                        @elseif (! empty($row['egress_colo']))
                                            {{ $row['egress_colo'] }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif

                        @if ($webRows !== [])
                            <tr class="bg-slate-900/5">
                                <td colspan="6" class="px-5 sm:px-8 py-3 text-xs sm:text-sm font-bold uppercase tracking-[0.16em] text-slate-500">
                                    Сайты в сети
                                </td>
                            </tr>
                            @foreach ($webRows as $row)
                                @php
                                    $status = (string) ($row['status'] ?? 'fail');
                                    $style = $statusStyles($status);
                                @endphp
                                <tr class="group border-b border-slate-100 last:border-b-0 border-l-4 {{ $style['row'] }} bg-gradient-to-r from-slate-50/80 to-white hover:from-slate-50 hover:to-slate-50/50 transition-colors">
                                    <td class="px-5 sm:px-8 py-5 sm:py-6">
                                        <div class="text-lg sm:text-xl font-bold text-slate-900 leading-tight">{{ $row['title'] ?? $row['id'] }}</div>
                                        <div class="text-xs sm:text-sm text-slate-400 mt-1">прямая проверка с hub</div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-5 sm:py-6">
                                        <span class="inline-flex items-center gap-2.5 rounded-full px-3.5 py-1.5 text-sm font-bold ring-1 {{ $style['badge'] }}">
                                            <span class="h-2.5 w-2.5 rounded-full shrink-0 {{ $style['dot'] }}" aria-hidden="true"></span>
                                            {{ $statusLabel($status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-5 sm:py-6 text-xl sm:text-2xl font-bold tabular-nums text-slate-900">
                                        {{ isset($row['latency_ms']) ? (int) $row['latency_ms'] : '—' }}
                                    </td>
                                    <td class="px-4 sm:px-6 py-5 sm:py-6 text-lg text-slate-300 font-medium">—</td>
                                    <td class="px-4 sm:px-6 py-5 sm:py-6 text-lg text-slate-300 font-medium">—</td>
                                    <td class="px-5 sm:px-8 py-5 sm:py-6 text-sm sm:text-base text-slate-500">
                                        @if (! empty($row['error']))
                                            <span class="text-rose-700">{{ $row['error'] }}</span>
                                        @else
                                            <span class="text-emerald-700 font-medium">страница открывается</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

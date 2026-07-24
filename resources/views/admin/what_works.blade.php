@extends('layouts.admin')

@section('title', 'Что работает?')

@php
    $metricTile = static function (?string $level): string {
        return match ($level) {
            'ok' => 'from-emerald-50 to-emerald-100 border-emerald-200/80',
            'warn' => 'from-amber-50 to-amber-100 border-amber-200/80',
            'fail' => 'from-rose-50 to-rose-100 border-rose-200/80',
            default => 'from-slate-50 to-slate-100 border-slate-200/80',
        };
    };

    /** @return array{wrap: string, sub: string, badge: string} */
    $vpnHeader = static function (?string $status): array {
        return match ($status) {
            'ok' => [
                'wrap' => 'bg-slate-900 text-white',
                'sub' => 'text-white/70',
                'badge' => 'bg-white/15 text-white ring-white/25',
            ],
            'warn' => [
                'wrap' => 'bg-amber-100 text-amber-950 border-b border-amber-200/90',
                'sub' => 'text-amber-800/80',
                'badge' => 'bg-amber-200/90 text-amber-950 ring-amber-300/80',
            ],
            'fail', 'skip' => [
                'wrap' => 'bg-rose-900 text-white',
                'sub' => 'text-white/70',
                'badge' => 'bg-white/15 text-white ring-white/25',
            ],
            default => [
                'wrap' => 'bg-slate-900 text-white',
                'sub' => 'text-white/70',
                'badge' => 'bg-white/15 text-white ring-white/25',
            ],
        };
    };

    /** @return array{wrap: string, sub: string, badge: string} */
    $webHeader = static function (bool $isOk): array {
        return $isOk
            ? [
                'wrap' => 'bg-emerald-50 text-emerald-950 border-b border-emerald-200/90',
                'sub' => 'text-emerald-800/75',
                'badge' => 'bg-emerald-200/90 text-emerald-950 ring-emerald-300/80',
            ]
            : [
                'wrap' => 'bg-rose-900 text-white',
                'sub' => 'text-white/70',
                'badge' => 'bg-white/15 text-white ring-white/25',
            ];
    };

    $vpnCardBorder = static function (?string $status): string {
        return match ($status) {
            'ok' => 'border-slate-200/90 shadow-lg shadow-slate-200/30',
            'warn' => 'border-amber-200/90 shadow-lg shadow-amber-100/40',
            default => 'border-rose-200/90 shadow-lg shadow-rose-100/40',
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
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6 sm:mb-8">
        <a
            href="{{ route('admin.dashboard') }}"
            class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 min-h-[44px]"
        >
            ← В меню
        </a>

        <form method="POST" action="{{ route('admin.what_works.run') }}" class="shrink-0 w-full sm:w-auto">
            @csrf
            <button
                type="submit"
                class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border-2 border-slate-900 bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:bg-slate-800 transition-colors min-h-[44px]"
            >
                Проверить сейчас
            </button>
        </form>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-white px-5 py-4 text-sm text-emerald-900 shadow-sm">
            {{ session('status') }}
        </div>
    @endif

    <ul class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-6 sm:mb-8 list-none p-0">
        <li class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50 p-4 sm:p-5 shadow-md shadow-slate-200/40">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">VPN · Happ</div>
            <div class="mt-2 text-2xl sm:text-3xl font-bold tabular-nums text-slate-900 tracking-tight">
                {{ $vpnOk }}<span class="text-slate-400 font-bold">/{{ count($vpnRows) }}</span>
            </div>
            <div class="mt-1 text-xs text-slate-500">каналов в норме</div>
        </li>
        <li class="rounded-2xl border border-emerald-200/70 bg-gradient-to-br from-emerald-50 to-white p-4 sm:p-5 shadow-md shadow-emerald-200/30">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-emerald-700/80">Сайты</div>
            <div class="mt-2 text-2xl sm:text-3xl font-bold tabular-nums text-emerald-700 tracking-tight">
                {{ $webOk }}<span class="text-emerald-500/70 font-bold">/{{ count($webRows) }}</span>
            </div>
            <div class="mt-1 text-xs text-emerald-700/70">в сети</div>
        </li>
        <li class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50 p-4 sm:p-5 shadow-md shadow-slate-200/40">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Последняя проверка</div>
            <div class="mt-2 text-base sm:text-lg font-bold text-slate-900 tracking-tight leading-snug">
                {{ $formatCheckedAt($results['checked_at'] ?? null) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">кэш {{ (int) $cacheTtl }} сек</div>
        </li>
    </ul>

    @if ($rows === [])
        <div class="rounded-2xl border-2 border-slate-200 bg-white p-6 sm:p-8 shadow-lg shadow-slate-200/30 text-center">
            <p class="text-sm sm:text-base text-slate-700 leading-relaxed max-w-lg mx-auto">
                @if (! ($results['from_cache'] ?? false))
                    Данных пока нет. Нажмите «Проверить сейчас» — прогон ~20–40 секунд.
                @else
                    Нет настроенных узлов. Заполните <code class="text-sm bg-slate-100 px-1.5 py-0.5 rounded">SUB_*_VLESS_URI</code>.
                @endif
            </p>
        </div>
    @else
        @if ($vpnRows !== [])
            <h2 class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 mb-3 sm:mb-4">VPN · Happ</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5 mb-8 sm:mb-10">
                @foreach ($vpnRows as $row)
                    @php
                        $status = (string) ($row['status'] ?? 'fail');
                        $tileLevel = $status === 'ok' ? 'ok' : ($status === 'warn' ? 'warn' : 'fail');
                        $hdr = $vpnHeader($status);
                    @endphp
                    <article class="rounded-2xl border-2 overflow-hidden flex flex-col min-w-0 bg-white {{ $vpnCardBorder($status) }}">
                        <header class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 px-4 py-3 {{ $hdr['wrap'] }}">
                            <div class="min-w-0">
                                <h3 class="text-base sm:text-lg font-bold tracking-tight break-words leading-snug">{{ $row['title'] ?? $row['id'] }}</h3>
                                <p class="text-[11px] sm:text-xs {{ $hdr['sub'] }} mt-0.5 uppercase tracking-wider">{{ $row['id'] ?? '' }}</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider shrink-0 ring-1 {{ $hdr['badge'] }}">
                                {{ $statusLabel($status) }}
                            </span>
                        </header>

                        <div class="p-3 sm:p-4 grid grid-cols-2 gap-2.5 flex-1 bg-slate-50/80">
                            <div class="rounded-xl border bg-gradient-to-br p-3 ring-1 ring-inset ring-white/70 shadow-sm {{ $metricTile($tileLevel) }}">
                                <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-slate-600">Latency</span>
                                <span class="block text-xl font-bold tabular-nums text-slate-900 mt-1.5">
                                    {{ isset($row['latency_ms']) ? (int) $row['latency_ms'] : '—' }}<span class="text-sm font-semibold text-slate-500"> ms</span>
                                </span>
                            </div>
                            <div class="rounded-xl border bg-gradient-to-br p-3 ring-1 ring-inset ring-white/70 shadow-sm {{ $metricTile($tileLevel) }}">
                                <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-slate-600">↓ Mbps</span>
                                <span class="block text-xl font-bold tabular-nums text-slate-900 mt-1.5">
                                    {{ isset($row['download_mbps']) ? number_format((float) $row['download_mbps'], 1, '.', '') : '—' }}
                                </span>
                            </div>
                            <div class="rounded-xl border bg-gradient-to-br p-3 ring-1 ring-inset ring-white/70 shadow-sm col-span-2 {{ $metricTile(($row['egress_ok'] ?? null) === false ? 'fail' : $tileLevel) }}">
                                <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-slate-600">Egress IP</span>
                                <span class="block text-sm font-mono font-bold text-slate-900 mt-1.5 break-all leading-snug">{{ $row['egress_ip'] ?? '—' }}</span>
                                @if (! empty($row['egress_colo']))
                                    <span class="block text-[10px] text-slate-500 mt-0.5">{{ $row['egress_colo'] }}</span>
                                @endif
                            </div>
                        </div>

                        @if (! empty($row['error']))
                            <p class="px-4 py-2.5 text-xs text-rose-800 bg-rose-50 border-t border-rose-100 break-words">{{ $row['error'] }}</p>
                        @endif

                        @if (! empty($row['note']))
                            <p class="px-4 py-2.5 text-xs text-sky-800 bg-sky-50 border-t border-sky-100 break-words">{{ $row['note'] }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif

        @if ($webRows !== [])
            <h2 class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 mb-3 sm:mb-4">Сайты в сети</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5">
                @foreach ($webRows as $row)
                    @php
                        $status = (string) ($row['status'] ?? 'fail');
                        $isOk = $status === 'ok';
                        $hdr = $webHeader($isOk);
                    @endphp
                    <article class="rounded-2xl border-2 overflow-hidden min-w-0 bg-white {{ $isOk ? 'border-emerald-200/80 shadow-lg shadow-emerald-100/30' : 'border-rose-200/80 shadow-lg shadow-rose-100/30' }}">
                        <header class="flex items-center justify-between gap-2 px-4 py-3 {{ $hdr['wrap'] }}">
                            <div class="min-w-0">
                                <h3 class="text-base sm:text-lg font-bold tracking-tight truncate">{{ $row['title'] ?? $row['id'] }}</h3>
                                <p class="text-[11px] sm:text-xs {{ $hdr['sub'] }} mt-0.5">проверка с hub</p>
                            </div>
                            <span class="shrink-0 inline-flex px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider ring-1 {{ $hdr['badge'] }}">
                                {{ $isOk ? 'В сети' : 'Недоступен' }}
                            </span>
                        </header>
                        <div class="p-3 sm:p-4 bg-slate-50/80">
                            <div class="rounded-xl border border-slate-200/80 bg-white p-3 sm:p-4 shadow-sm">
                                @if ($isOk && isset($row['latency_ms']))
                                    <div class="text-[10px] font-bold uppercase tracking-[0.1em] text-slate-500">Отклик</div>
                                    <div class="mt-1.5 text-xl font-bold tabular-nums text-slate-900">
                                        {{ (int) $row['latency_ms'] }}<span class="text-sm font-semibold text-slate-500"> ms</span>
                                    </div>
                                    <p class="mt-1.5 text-xs sm:text-sm text-emerald-700 font-medium">Страница открывается</p>
                                @else
                                    <p class="text-sm text-rose-700 break-words">{{ $row['error'] ?? 'Не удалось открыть' }}</p>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    @endif
@endsection

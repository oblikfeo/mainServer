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

    $headerClass = static function (?string $status): string {
        return match ($status) {
            'ok' => 'bg-slate-900',
            'warn' => 'bg-amber-900',
            'fail', 'skip' => 'bg-rose-900',
            default => 'bg-slate-900',
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
            class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 min-h-[44px]"
        >
            ← В меню
        </a>

        <form method="POST" action="{{ route('admin.what_works.run') }}" class="shrink-0 w-full sm:w-auto">
            @csrf
            <button
                type="submit"
                class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border-2 border-slate-900 bg-slate-900 px-5 py-2.5 text-sm sm:text-base font-semibold text-white shadow-md hover:bg-slate-800 transition-colors min-h-[44px]"
            >
                Проверить сейчас
            </button>
        </form>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-emerald-200/80 bg-gradient-to-r from-emerald-50 to-white px-5 py-4 text-sm sm:text-base text-emerald-900 shadow-sm">
            {{ session('status') }}
        </div>
    @endif

    <ul class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-6 sm:mb-10 list-none p-0">
        <li class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50 p-4 sm:p-6 shadow-md shadow-slate-200/40">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">VPN · Happ</div>
            <div class="mt-2 sm:mt-3 text-3xl sm:text-4xl font-bold tabular-nums text-slate-900 tracking-tight">
                {{ $vpnOk }}<span class="text-slate-400 font-bold">/{{ count($vpnRows) }}</span>
            </div>
            <div class="mt-1 text-xs sm:text-sm text-slate-500">каналов в норме</div>
        </li>
        <li class="rounded-2xl border border-emerald-200/70 bg-gradient-to-br from-emerald-50 to-white p-4 sm:p-6 shadow-md shadow-emerald-200/30">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-emerald-700/80">Сайты</div>
            <div class="mt-2 sm:mt-3 text-3xl sm:text-4xl font-bold tabular-nums text-emerald-700 tracking-tight">
                {{ $webOk }}<span class="text-emerald-500/70 font-bold">/{{ count($webRows) }}</span>
            </div>
            <div class="mt-1 text-xs sm:text-sm text-emerald-700/70">в сети</div>
        </li>
        <li class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white to-slate-50 p-4 sm:p-6 shadow-md shadow-slate-200/40">
            <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Последняя проверка</div>
            <div class="mt-2 sm:mt-3 text-lg sm:text-xl font-bold text-slate-900 tracking-tight leading-snug">
                {{ $formatCheckedAt($results['checked_at'] ?? null) }}
            </div>
            <div class="mt-1 text-xs sm:text-sm text-slate-500">кэш {{ (int) $cacheTtl }} сек</div>
        </li>
    </ul>

    @if ($rows === [])
        <div class="rounded-3xl border-2 border-slate-200 bg-white p-6 sm:p-10 shadow-lg shadow-slate-200/30 text-center">
            <p class="text-base sm:text-lg text-slate-700 leading-relaxed max-w-lg mx-auto">
                @if (! ($results['from_cache'] ?? false))
                    Данных пока нет. Нажмите «Проверить сейчас» — прогон ~20–40 секунд. Обычное открытие страницы быстрое.
                @else
                    Нет настроенных узлов. Заполните <code class="text-sm bg-slate-100 px-1.5 py-0.5 rounded">SUB_*_VLESS_URI</code>.
                @endif
            </p>
        </div>
    @else
        @if ($vpnRows !== [])
            <h2 class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 mb-4 sm:mb-5">VPN · Happ</h2>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 sm:gap-8 mb-10 sm:mb-12">
                @foreach ($vpnRows as $row)
                    @php
                        $status = (string) ($row['status'] ?? 'fail');
                        $tileLevel = $status === 'ok' ? 'ok' : ($status === 'warn' ? 'warn' : 'fail');
                    @endphp
                    <article class="rounded-3xl border-2 overflow-hidden flex flex-col min-w-0 {{ $status === 'ok' ? 'border-slate-200/90 bg-white shadow-xl shadow-slate-300/25' : 'border-rose-200/90 bg-white shadow-lg shadow-rose-200/20' }}">
                        <header class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 px-5 sm:px-6 py-4 sm:py-5 text-white {{ $headerClass($status) }}">
                            <div class="min-w-0">
                                <h3 class="text-lg sm:text-xl font-bold tracking-tight break-words">{{ $row['title'] ?? $row['id'] }}</h3>
                                <p class="text-xs sm:text-sm text-white/70 mt-1 uppercase tracking-wider">{{ $row['id'] ?? '' }}</p>
                            </div>
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider shrink-0 bg-white/15 ring-1 ring-white/25">
                                {{ $statusLabel($status) }}
                            </span>
                        </header>

                        <div class="p-4 sm:p-5 grid grid-cols-2 gap-3 flex-1 bg-slate-50/80">
                            <div class="rounded-2xl border bg-gradient-to-br p-4 ring-1 ring-inset ring-white/70 shadow-sm {{ $metricTile($tileLevel) }}">
                                <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-600">Latency</span>
                                <span class="block text-2xl sm:text-3xl font-bold tabular-nums text-slate-900 mt-2">
                                    {{ isset($row['latency_ms']) ? (int) $row['latency_ms'] : '—' }}<span class="text-base sm:text-lg font-semibold text-slate-500"> ms</span>
                                </span>
                            </div>
                            <div class="rounded-2xl border bg-gradient-to-br p-4 ring-1 ring-inset ring-white/70 shadow-sm {{ $metricTile($tileLevel) }}">
                                <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-600">Скорость ↓</span>
                                <span class="block text-2xl sm:text-3xl font-bold tabular-nums text-slate-900 mt-2">
                                    @if (isset($row['download_mbps']))
                                        {{ number_format((float) $row['download_mbps'], 1, '.', '') }}<span class="text-base sm:text-lg font-semibold text-slate-500"> Mbps</span>
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                            <div class="rounded-2xl border bg-gradient-to-br p-4 ring-1 ring-inset ring-white/70 shadow-sm col-span-2 {{ $metricTile(($row['egress_ok'] ?? null) === false ? 'fail' : $tileLevel) }}">
                                <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-600">Egress IP</span>
                                <span class="block text-base sm:text-lg font-mono font-bold text-slate-900 mt-2 break-all leading-snug">{{ $row['egress_ip'] ?? '—' }}</span>
                                @if (! empty($row['egress_colo']))
                                    <span class="block text-[10px] text-slate-500 mt-1">{{ $row['egress_colo'] }}</span>
                                @endif
                            </div>
                        </div>

                        @if (! empty($row['error']))
                            <p class="px-5 py-3 text-sm text-rose-800 bg-rose-50 border-t border-rose-100 break-words">{{ $row['error'] }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif

        @if ($webRows !== [])
            <h2 class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 mb-4 sm:mb-5">Сайты в сети</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                @foreach ($webRows as $row)
                    @php
                        $status = (string) ($row['status'] ?? 'fail');
                        $isOk = $status === 'ok';
                    @endphp
                    <article class="rounded-3xl border-2 overflow-hidden min-w-0 {{ $isOk ? 'border-emerald-200/80 shadow-lg shadow-emerald-200/20' : 'border-rose-200/80 shadow-lg shadow-rose-200/20' }}">
                        <header class="flex items-center justify-between gap-3 px-5 sm:px-6 py-4 sm:py-5 text-white {{ $isOk ? 'bg-emerald-900' : 'bg-rose-900' }}">
                            <div class="min-w-0">
                                <h3 class="text-lg sm:text-xl font-bold tracking-tight truncate">{{ $row['title'] ?? $row['id'] }}</h3>
                                <p class="text-xs sm:text-sm text-white/70 mt-0.5">проверка с hub</p>
                            </div>
                            <span class="shrink-0 inline-flex px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-white/15 ring-1 ring-white/25">
                                {{ $isOk ? 'В сети' : 'Недоступен' }}
                            </span>
                        </header>
                        <div class="p-4 sm:p-5 bg-gradient-to-br from-white to-slate-50">
                            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 sm:p-5 shadow-sm">
                                @if ($isOk && isset($row['latency_ms']))
                                    <div class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-500">Отклик</div>
                                    <div class="mt-2 text-2xl sm:text-3xl font-bold tabular-nums text-slate-900">
                                        {{ (int) $row['latency_ms'] }}<span class="text-base font-semibold text-slate-500"> ms</span>
                                    </div>
                                    <p class="mt-2 text-sm text-emerald-700 font-medium">Страница открывается</p>
                                @else
                                    <p class="text-sm sm:text-base text-rose-700 break-words">{{ $row['error'] ?? 'Не удалось открыть' }}</p>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    @endif
@endsection

@extends('layouts.admin')

@section('title', '')

@php
    $statusTile = static function (?string $status): string {
        return match ($status) {
            'ok' => 'from-emerald-50 to-emerald-100 border-emerald-200/80',
            'warn' => 'from-amber-50 to-amber-100 border-amber-200/80',
            'fail' => 'from-rose-50 to-rose-100 border-rose-200/80',
            'skip' => 'from-slate-50 to-slate-100 border-slate-200/80',
            default => 'from-slate-50 to-slate-100 border-slate-200/80',
        };
    };

    $headerClass = static function (?string $status): string {
        return match ($status) {
            'ok' => 'bg-emerald-900',
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
            return \Illuminate\Support\Carbon::parse($iso)->timezone(config('app.timezone'))->format('d.m.Y H:i:s');
        } catch (\Throwable) {
            return $iso;
        }
    };

    $nodes = is_array($results['nodes'] ?? null) ? $results['nodes'] : [];
@endphp

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 sm:mb-8">
        <a
            href="{{ route('admin.dashboard') }}"
            class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 min-h-[44px]"
        >
            ← В меню
        </a>

        <form method="POST" action="{{ route('admin.what_works.run') }}" class="shrink-0">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-xl border border-slate-900 bg-slate-900 px-5 py-2.5 text-sm sm:text-base font-semibold text-white shadow-sm hover:bg-slate-800 min-h-[44px]"
            >
                Проверить сейчас
            </button>
        </form>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm mb-6 sm:mb-8">
        <p class="text-sm sm:text-base text-slate-700 leading-relaxed">
            Проверка идёт <strong>с hub</strong> через локальный Xray-клиент и те же общие <code class="text-xs bg-slate-100 px-1 py-0.5 rounded">vless://</code>, что в Happ.
            Удалённые VPS только принимают соединение — без SSH и без правок конфигов.
        </p>
        <dl class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
            <div>
                <dt class="text-slate-500">Последний прогон</dt>
                <dd class="font-semibold text-slate-900">{{ $formatCheckedAt($results['checked_at'] ?? null) }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Кэш</dt>
                <dd class="font-semibold text-slate-900">{{ (int) $cacheTtl }} сек</dd>
            </div>
            <div>
                <dt class="text-slate-500">Xray на hub</dt>
                <dd class="font-semibold {{ ($results['xray_available'] ?? false) ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ ($results['xray_available'] ?? false) ? 'найден' : 'не найден' }}
                </dd>
            </div>
        </dl>
    </div>

    @if ($nodes === [])
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-900">
            Нет настроенных узлов для проверки. Заполните <code class="text-xs bg-white/70 px-1 rounded">SUB_*_VLESS_URI</code> в .env.
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 sm:gap-8">
        @foreach ($nodes as $node)
            @php
                $status = (string) ($node['status'] ?? 'fail');
                $sites = is_array($node['sites'] ?? null) ? $node['sites'] : [];
            @endphp
            <article class="rounded-3xl border-2 overflow-hidden flex flex-col min-h-0 border-slate-200/90 bg-white shadow-xl shadow-slate-300/20">
                <header class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 px-6 py-5 text-white {{ $headerClass($status) }}">
                    <div class="min-w-0">
                        <h2 class="text-xl sm:text-2xl font-bold tracking-tight">{{ $node['title'] ?? $node['id'] ?? 'Узел' }}</h2>
                        <p class="text-sm text-white/75 mt-1 uppercase tracking-wider">{{ $node['id'] ?? '' }}</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider shrink-0 bg-white/15 ring-1 ring-white/25">
                        {{ $statusLabel($status) }}
                    </span>
                </header>

                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3 flex-1 bg-slate-50/80">
                    <div class="rounded-2xl border bg-gradient-to-br p-4 sm:col-span-2 {{ $statusTile($status) }}">
                        <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-600">Туннель (клиентский путь)</span>
                        <span class="block text-2xl font-bold tabular-nums text-slate-900 mt-2">
                            {{ ($node['tunnel_ok'] ?? false) ? 'OK' : 'FAIL' }}
                        </span>
                        @if (! empty($node['error']))
                            <span class="block text-xs text-slate-600 mt-2 break-words">{{ $node['error'] }}</span>
                        @endif
                    </div>

                    <div class="rounded-2xl border bg-gradient-to-br p-4 {{ $statusTile(null) }}">
                        <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-600">Latency</span>
                        <span class="block text-2xl font-bold tabular-nums text-slate-900 mt-2">
                            {{ isset($node['latency_ms']) ? (int) $node['latency_ms'].' ms' : '—' }}
                        </span>
                        @if (isset($node['handshake_ms']))
                            <span class="block text-[10px] text-slate-500 mt-1">TLS/Reality ~ {{ (int) $node['handshake_ms'] }} ms</span>
                        @endif
                    </div>

                    <div class="rounded-2xl border bg-gradient-to-br p-4 {{ $statusTile(null) }}">
                        <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-600">Скорость ↓</span>
                        <span class="block text-2xl font-bold tabular-nums text-slate-900 mt-2">
                            {{ isset($node['download_mbps']) ? number_format((float) $node['download_mbps'], 1, '.', '').' Mbps' : '—' }}
                        </span>
                    </div>

                    <div class="rounded-2xl border bg-gradient-to-br p-4 sm:col-span-2 {{ $statusTile(($node['egress_ok'] ?? null) === false ? 'fail' : (($node['egress_ok'] ?? null) === true ? 'ok' : null)) }}">
                        <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-600">Egress IP</span>
                        <span class="block text-xl sm:text-2xl font-bold tabular-nums text-slate-900 mt-2 break-all">
                            {{ $node['egress_ip'] ?? '—' }}
                        </span>
                        @if (! empty($node['egress_colo']))
                            <span class="block text-[10px] text-slate-500 mt-1">colo {{ $node['egress_colo'] }}</span>
                        @endif
                        @if (($node['egress_ok'] ?? null) === false)
                            <span class="block text-xs text-rose-700 mt-2">IP не совпадает с ожидаемым или цепочка egress сломана</span>
                        @endif
                    </div>

                    @if ($sites !== [])
                        <div class="rounded-2xl border bg-gradient-to-br p-4 sm:col-span-2 {{ $statusTile(null) }}">
                            <span class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-600">Сайты через туннель</span>
                            <ul class="mt-3 flex flex-wrap gap-2 list-none p-0">
                                @foreach ($sites as $site)
                                    <li class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ ($site['ok'] ?? false) ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                        {{ $site['label'] ?? $site['key'] ?? 'site' }}
                                        @if ($site['ok'] ?? false)
                                            · {{ isset($site['ms']) ? (int) $site['ms'].' ms' : 'OK' }}
                                        @else
                                            · FAIL
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="sm:col-span-2 text-[11px] text-slate-500">
                        Проверка узла: {{ $formatCheckedAt($node['checked_at'] ?? null) }}
                        @if (isset($node['duration_ms']))
                            · {{ (int) $node['duration_ms'] }} ms на прогон
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endsection

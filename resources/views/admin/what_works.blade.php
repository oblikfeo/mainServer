@extends('layouts.admin')

@section('title', '')

@php
    $statusBadge = static function (?string $status): string {
        return match ($status) {
            'ok' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'warn' => 'bg-amber-100 text-amber-900 ring-amber-200',
            'fail' => 'bg-rose-100 text-rose-800 ring-rose-200',
            'skip' => 'bg-slate-100 text-slate-600 ring-slate-200',
            default => 'bg-slate-100 text-slate-600 ring-slate-200',
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

    $formatCheckedAt = static function (?string $iso): string {
        if ($iso === null || $iso === '') {
            return '—';
        }
        try {
            return \Illuminate\Support\Carbon::parse($iso)->timezone(config('app.timezone'))->format('d.m H:i:s');
        } catch (\Throwable) {
            return $iso;
        }
    };

    $siteCell = static function (array $sites, string $key): ?array {
        foreach ($sites as $site) {
            if (is_array($site) && ($site['key'] ?? '') === $key) {
                return $site;
            }
        }

        return null;
    };

    $sitePill = static function (?array $site): string {
        if ($site === null) {
            return 'text-slate-400';
        }

        return ($site['ok'] ?? false)
            ? 'text-emerald-700 font-semibold'
            : 'text-rose-700 font-semibold';
    };

    $nodes = is_array($results['nodes'] ?? null) ? $results['nodes'] : [];
    $siteColumns = collect(config('path_probe.sites', []))
        ->filter(fn ($s) => is_array($s) && ($s['key'] ?? '') !== '' && ($s['url'] ?? '') !== '')
        ->values()
        ->all();
    $mainSiteUrl = trim((string) (config('path_probe.sites.0.url') ?? 'https://nadezhda.space'));
    $seoSiteUrl = trim((string) (config('path_probe.sites.1.url') ?? 'https://nadezhda.info'));
@endphp

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 sm:mb-6">
        <a
            href="{{ route('admin.dashboard') }}"
            class="inline-flex items-center justify-center self-start rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 min-h-[40px]"
        >
            ← Меню
        </a>

        <form method="POST" action="{{ route('admin.what_works.run') }}" class="shrink-0">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg border border-slate-900 bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 min-h-[40px]"
            >
                Проверить сейчас
            </button>
        </form>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">
            {{ session('status') }}
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs sm:text-sm text-slate-600">
        <span>Hub → Xray → общие VLESS из Happ</span>
        <span class="hidden sm:inline text-slate-300">·</span>
        <span>Прогон {{ $formatCheckedAt($results['checked_at'] ?? null) }}</span>
        <span class="hidden sm:inline text-slate-300">·</span>
        <span>кэш {{ (int) $cacheTtl }} с</span>
        <span class="hidden sm:inline text-slate-300">·</span>
        <span class="{{ ($results['xray_available'] ?? false) ? 'text-emerald-700' : 'text-rose-700' }}">
            Xray {{ ($results['xray_available'] ?? false) ? 'OK' : 'нет' }}
        </span>
    </div>

    @if ($nodes === [])
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Нет настроенных узлов. Заполните <code class="text-xs bg-white/70 px-1 rounded">SUB_*_VLESS_URI</code>.
        </div>
    @else
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-left text-[11px] uppercase tracking-wide text-slate-500">
                            <th class="px-3 py-2.5 font-bold min-w-[9rem]">Узел</th>
                            <th class="px-2 py-2.5 font-bold w-20">Статус</th>
                            <th class="px-2 py-2.5 font-bold w-16">Тунн.</th>
                            <th class="px-2 py-2.5 font-bold w-16 tabular-nums">ms</th>
                            <th class="px-2 py-2.5 font-bold w-20 tabular-nums">↓ Mbps</th>
                            <th class="px-2 py-2.5 font-bold min-w-[7rem]">Egress IP</th>
                            @foreach ($siteColumns as $col)
                                <th class="px-2 py-2.5 font-bold w-16 text-center" title="{{ $col['url'] ?? '' }}">
                                    {{ $col['label'] ?? $col['key'] }}
                                </th>
                            @endforeach
                            <th class="px-3 py-2.5 font-bold min-w-[8rem]">Ошибка</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($nodes as $node)
                            @php
                                $status = (string) ($node['status'] ?? 'fail');
                                $sites = is_array($node['sites'] ?? null) ? $node['sites'] : [];
                            @endphp
                            <tr class="hover:bg-slate-50/80 align-top">
                                <td class="px-3 py-2.5">
                                    <div class="font-semibold text-slate-900 leading-tight">{{ $node['title'] ?? $node['id'] }}</div>
                                    <div class="text-[10px] uppercase tracking-wider text-slate-400 mt-0.5">{{ $node['id'] ?? '' }}</div>
                                </td>
                                <td class="px-2 py-2.5">
                                    <span class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-bold ring-1 {{ $statusBadge($status) }}">
                                        {{ $statusLabel($status) }}
                                    </span>
                                </td>
                                <td class="px-2 py-2.5 tabular-nums {{ ($node['tunnel_ok'] ?? false) ? 'text-emerald-700 font-semibold' : 'text-rose-700 font-semibold' }}">
                                    {{ ($node['tunnel_ok'] ?? false) ? 'OK' : 'FAIL' }}
                                </td>
                                <td class="px-2 py-2.5 tabular-nums text-slate-800">
                                    {{ isset($node['latency_ms']) ? (int) $node['latency_ms'] : '—' }}
                                </td>
                                <td class="px-2 py-2.5 tabular-nums text-slate-800">
                                    {{ isset($node['download_mbps']) ? number_format((float) $node['download_mbps'], 1, '.', '') : '—' }}
                                </td>
                                <td class="px-2 py-2.5">
                                    <div class="font-mono text-xs text-slate-800 break-all">{{ $node['egress_ip'] ?? '—' }}</div>
                                    @if (($node['egress_ok'] ?? null) === false)
                                        <div class="text-[10px] text-rose-600 mt-0.5">egress ✗</div>
                                    @elseif (! empty($node['egress_colo']))
                                        <div class="text-[10px] text-slate-400 mt-0.5">{{ $node['egress_colo'] }}</div>
                                    @endif
                                </td>
                                @foreach ($siteColumns as $col)
                                    @php $site = $siteCell($sites, (string) ($col['key'] ?? '')); @endphp
                                    <td class="px-2 py-2.5 text-center tabular-nums {{ $sitePill($site) }}">
                                        @if ($site === null)
                                            —
                                        @elseif ($site['ok'] ?? false)
                                            {{ isset($site['ms']) ? (int) $site['ms'] : 'OK' }}
                                        @else
                                            FAIL
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-2.5 text-xs text-slate-500 break-words max-w-[14rem]">
                                    @if (! empty($node['error']))
                                        {{ $node['error'] }}
                                    @else
                                        <span class="text-slate-400">{{ $formatCheckedAt($node['checked_at'] ?? null) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <p class="mt-3 text-[11px] text-slate-500 leading-relaxed">
            Колонки «Основной» и «SEO» — доступ к
            <span class="font-mono">{{ $mainSiteUrl }}</span> и
            <span class="font-mono">{{ $seoSiteUrl }}</span> через туннель (не с hub напрямую).
            Цифры в колонках сайтов — latency, мс.
        </p>
    @endif
@endsection

@extends('layouts.admin')

@section('title', 'Узлы подписки')

@php
    $providerBadge = static function (?string $provider): string {
        return match (true) {
            $provider === null => 'bg-amber-100 text-amber-900 ring-amber-200',
            str_contains($provider, 'CDN') => 'bg-violet-50 text-violet-800 ring-violet-200',
            str_contains($provider, 'PLAY2GO') => 'bg-sky-50 text-sky-800 ring-sky-200',
            default => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        };
    };
    $pools = [
        'wifi' => ['title' => 'Wi-Fi', 'icon' => '📶', 'rows' => $pools['wifi']],
        'lte' => ['title' => 'Анти-глушилки', 'icon' => '🛡️', 'rows' => $pools['lte']],
    ];
@endphp

@section('content')
    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← В меню
    </a>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-900 text-left text-[11px] font-bold uppercase tracking-[0.1em] text-white/80">
                        <th class="px-4 py-3 whitespace-nowrap">Узел</th>
                        <th class="px-4 py-3 whitespace-nowrap">🌐 Адрес</th>
                        <th class="px-4 py-3 whitespace-nowrap">🏢 Провайдер</th>
                        <th class="px-4 py-3 whitespace-nowrap">📍 Где сервер</th>
                        <th class="px-4 py-3 whitespace-nowrap">🔒 Протокол</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pools as $poolKey => $pool)
                        <tr class="bg-slate-100 border-t border-slate-200">
                            <td colspan="5" class="px-4 py-2 font-bold text-slate-700 whitespace-nowrap">
                                {{ $pool['icon'] }} {{ $pool['title'] }}
                                <span class="ml-1 rounded-full bg-white px-2 py-0.5 text-xs text-slate-500 ring-1 ring-slate-200">{{ count($pool['rows']) }}</span>
                            </td>
                        </tr>
                        @forelse ($pool['rows'] as $node)
                            <tr class="border-t border-slate-100 hover:bg-slate-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">{{ $node['name'] !== '' ? $node['name'] : 'без названия' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ strtoupper($poolKey) }}_{{ $node['slot'] }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-slate-900">
                                    {{ $node['host'] }}<span class="text-slate-400">:{{ $node['port'] }}</span>
                                    @if ($node['egress'])
                                        <div class="text-[11px] text-slate-500 font-sans">выход ↗ <span class="font-mono">{{ $node['egress'] }}</span></div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex rounded-lg px-2 py-1 text-xs font-bold ring-1 {{ $providerBadge($node['provider']) }}">
                                        {{ $node['provider'] ?? 'нет в справочнике' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $node['location'] ?? '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @foreach (explode(' · ', $node['protocol']) as $tag)
                                        <span class="inline-flex rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-700 mr-0.5">{{ $tag }}</span>
                                    @endforeach
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-slate-100">
                                <td colspan="5" class="px-4 py-3 text-slate-400">Пусто</td>
                            </tr>
                        @endforelse
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

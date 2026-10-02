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
    $sections = [
        'wifi' => ['title' => 'Wi-Fi', 'icon' => '📶'],
        'lte' => ['title' => 'Анти-глушилки', 'icon' => '🛡️'],
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
                        <th class="px-4 py-3 whitespace-nowrap">🖥️ Сервер</th>
                        <th class="px-4 py-3 whitespace-nowrap">🏢 Провайдер</th>
                        <th class="px-4 py-3 whitespace-nowrap">📍 Где</th>
                        <th class="px-4 py-3 whitespace-nowrap">🔗 Кнопка</th>
                        <th class="px-4 py-3 whitespace-nowrap">🚪 Порт</th>
                        <th class="px-4 py-3 whitespace-nowrap">🔒 Протокол</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sections as $poolKey => $section)
                        @php
                            $servers = $pools[$poolKey];
                            $linkCount = array_sum(array_map(static fn ($s) => count($s['links']), $servers));
                        @endphp
                        <tr class="bg-slate-100 border-t-2 border-slate-300">
                            <td colspan="6" class="px-4 py-2 font-bold text-slate-700 whitespace-nowrap">
                                {{ $section['icon'] }} {{ $section['title'] }}
                                <span class="ml-2 rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">
                                    серверов: {{ count($servers) }} · ссылок: {{ $linkCount }}
                                </span>
                            </td>
                        </tr>
                        @forelse ($servers as $server)
                            @foreach ($server['links'] as $link)
                                <tr class="{{ $loop->first ? 'border-t-2 border-slate-200' : 'border-t border-dashed border-slate-100' }} hover:bg-slate-50">
                                    @if ($loop->first)
                                        @php $span = count($server['links']); @endphp
                                        <td rowspan="{{ $span }}" class="px-4 py-3 align-top whitespace-nowrap border-r border-slate-100 bg-white">
                                            <div class="font-mono font-bold text-slate-900">{{ $server['host'] }}</div>
                                            @if ($server['egress'])
                                                <div class="text-[11px] text-slate-500">выход ↗ <span class="font-mono">{{ $server['egress'] }}</span></div>
                                            @endif
                                            @if ($span > 1)
                                                <div class="mt-1 inline-flex rounded-md bg-slate-900 px-1.5 py-0.5 text-[11px] font-bold text-white">{{ $span }} ссылки на одном сервере</div>
                                            @endif
                                        </td>
                                        <td rowspan="{{ $span }}" class="px-4 py-3 align-top whitespace-nowrap bg-white">
                                            <span class="inline-flex rounded-lg px-2 py-1 text-xs font-bold ring-1 {{ $providerBadge($server['provider']) }}">
                                                {{ $server['provider'] ?? 'нет в справочнике' }}
                                            </span>
                                        </td>
                                        <td rowspan="{{ $span }}" class="px-4 py-3 align-top text-slate-700 border-r border-slate-100 bg-white">
                                            <div class="font-semibold text-slate-900">{{ $server['location'] ?? '—' }}</div>
                                            @if ($server['geo'] || $server['google'])
                                                <div class="mt-1 flex flex-wrap gap-1 text-[11px]">
                                                    @if ($server['geo'])
                                                        <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-slate-600">геобаза: {{ $server['geo'] }}</span>
                                                    @endif
                                                    @if ($server['google'])
                                                        <span class="rounded-md px-1.5 py-0.5 {{ $server['google'] === 'Россия' ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">Google: {{ $server['google'] }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-bold text-slate-900">{{ $link['name'] !== '' ? $link['name'] : 'без названия' }}</div>
                                        <div class="text-[11px] text-slate-400">{{ strtoupper($poolKey) }}_{{ $link['slot'] }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap font-mono font-bold text-slate-900">{{ $link['port'] }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @foreach (explode(' · ', $link['protocol']) as $tag)
                                            <span class="inline-flex rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-700 mr-0.5">{{ $tag }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr class="border-t border-slate-100">
                                <td colspan="6" class="px-4 py-3 text-slate-400">Пусто</td>
                            </tr>
                        @endforelse
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

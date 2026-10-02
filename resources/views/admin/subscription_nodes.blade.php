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
                        <th class="px-4 py-3 whitespace-nowrap">🔗 Кнопки в подписке</th>
                        <th class="px-4 py-3 whitespace-nowrap">🖥️ Сервер</th>
                        <th class="px-4 py-3 whitespace-nowrap">🏢 Провайдер</th>
                        <th class="px-4 py-3 whitespace-nowrap">📍 Где</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (['wifi' => ['📶', 'Wi-Fi'], 'lte' => ['🛡️', 'Анти-глушилки']] as $poolKey => [$icon, $title])
                        <tr class="bg-slate-100 border-t-2 border-slate-300">
                            <td colspan="4" class="px-4 py-2 font-bold text-slate-700 whitespace-nowrap">
                                {{ $icon }} {{ $title }}
                                <span class="ml-2 rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-slate-500 ring-1 ring-slate-200">{{ count($pools[$poolKey]) }}</span>
                            </td>
                        </tr>
                        @foreach ($pools[$poolKey] as $server)
                            <tr class="border-t border-slate-200 hover:bg-slate-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($server['links'] as $link)
                                            <span class="inline-flex rounded-lg bg-slate-900 px-2.5 py-1 text-xs font-bold text-white">{{ $link['name'] }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono font-bold text-slate-900">{{ $server['host'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex rounded-lg px-2 py-1 text-xs font-bold ring-1 {{ $providerBadge($server['provider']) }}">
                                        {{ $server['provider'] ?? 'нет в справочнике' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-semibold text-slate-900">{{ $server['location'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

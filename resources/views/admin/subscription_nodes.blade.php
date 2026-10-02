@extends('layouts.admin')

@section('title', 'Узлы подписки')

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
                        <th class="px-4 py-3 whitespace-nowrap">Кнопки в подписке</th>
                        <th class="px-4 py-3 whitespace-nowrap">Сервер</th>
                        <th class="px-4 py-3 whitespace-nowrap">Провайдер</th>
                        <th class="px-4 py-3 whitespace-nowrap">Где</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (['wifi' => '📶 Wi-Fi', 'lte' => '🛡️ Анти-глушилки'] as $poolKey => $title)
                        <tr class="bg-slate-100 border-t border-slate-200">
                            <td colspan="4" class="px-4 py-2 font-bold text-slate-700">{{ $title }}</td>
                        </tr>
                        @foreach ($pools[$poolKey] as $server)
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3 font-bold text-slate-900 whitespace-nowrap">
                                    @foreach ($server['links'] as $link)
                                        <div>{{ $link['name'] }}</div>
                                    @endforeach
                                </td>
                                <td class="px-4 py-3 font-mono text-slate-900 whitespace-nowrap">{{ $server['host'] }}</td>
                                <td class="px-4 py-3 text-slate-900 whitespace-nowrap">{{ $server['provider'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $server['location'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

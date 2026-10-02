@extends('layouts.admin')

@section('title', 'Узлы подписки')

@section('content')
    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← В меню
    </a>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm mb-6 text-sm text-slate-700 space-y-1">
        <div>
            Формат подписки: <b class="text-slate-900">{{ $feedFormat }}</b>
            @if ($feedFormat === 'auto')
                — клиент видит одну кнопку <b class="text-slate-900">{{ $autoTitle }}</b>, узлы ниже работают внутри неё.
            @endif
        </div>
        <div>Wi-Fi пул: <b class="text-slate-900">{{ $wifiStrategy }}</b> по живым узлам. Если весь Wi-Fi пул мёртв — уходит в «Анти-глушилки».</div>
        <div class="text-slate-500">Узлы меняются строками SUB_AUTO_WIFI_N / SUB_AUTO_LTE_N в .env хаба, подписи серверов — config/sub_nodes.php.</div>
    </div>

    @foreach (['wifi' => 'Wi-Fi пул', 'lte' => 'Анти-глушилки (LTE, запасной пул)'] as $poolKey => $poolTitle)
        <h2 class="text-lg sm:text-xl font-bold text-slate-900 mb-3 {{ $loop->first ? '' : 'mt-8' }}">
            {{ $poolTitle }} <span class="text-slate-400 font-semibold">· {{ count($pools[$poolKey]) }}</span>
        </h2>

        @if ($pools[$poolKey] === [])
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-slate-500">Пул пуст.</div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4">
                @foreach ($pools[$poolKey] as $node)
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">{{ strtoupper($poolKey) }}_{{ $node['slot'] }}</div>
                                <div class="text-base sm:text-lg font-bold text-slate-900 break-words">{{ $node['name'] !== '' ? $node['name'] : 'без названия' }}</div>
                            </div>
                            @if ($node['provider'])
                                <span class="shrink-0 rounded-lg bg-slate-900 px-2.5 py-1 text-xs font-bold text-white">{{ $node['provider'] }}</span>
                            @else
                                <span class="shrink-0 rounded-lg bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-900">нет в справочнике</span>
                            @endif
                        </div>

                        <dl class="mt-3 grid gap-x-4 gap-y-1.5 text-sm" style="grid-template-columns: auto minmax(0, 1fr);">
                            <dt class="text-slate-500">Адрес</dt>
                            <dd class="font-mono text-slate-900 break-all">{{ $node['host'] }}:{{ $node['port'] }}</dd>

                            @if ($node['egress'])
                                <dt class="text-slate-500">Выход</dt>
                                <dd class="font-mono text-slate-900">{{ $node['egress'] }}</dd>
                            @endif

                            <dt class="text-slate-500">Где</dt>
                            <dd class="text-slate-900">{{ $node['location'] ?? '—' }}</dd>

                            <dt class="text-slate-500">Протокол</dt>
                            <dd class="text-slate-900">{{ $node['protocol'] }}</dd>

                            @if ($node['server'])
                                <dt class="text-slate-500">Папка</dt>
                                <dd class="text-slate-900">servers/…/{{ $node['server'] }}</dd>
                            @endif
                        </dl>
                    </div>
                @endforeach
            </div>
        @endif
    @endforeach
@endsection

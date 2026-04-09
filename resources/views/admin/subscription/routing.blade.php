@extends('layouts.admin')

@section('title', 'Обход Direct')

@section('content')
    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← В меню
    </a>

    <div class="max-w-3xl w-full mx-auto space-y-6">
        <h1 class="text-xl sm:text-3xl font-bold text-slate-900 tracking-tight">Обход Direct</h1>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-950 text-sm font-medium">
                {{ session('status') }}
            </div>
        @endif

        <form
            method="post"
            action="{{ route('admin.subscription.routing.update') }}"
            class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4"
        >
            @csrf
            @if (! $happRoutingEnabled)
                <p class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                    Выключено в .env: <span class="font-mono text-xs">HAPP_ROUTING_ENABLED=false</span> — список сохраняется, в клиент не уходит.
                </p>
            @endif
            <p class="text-sm text-slate-600">
                Одна строка = одно правило. Можно вставлять: ссылку, домен, IP/CIDR, либо готовую запись <span class="font-mono text-xs">domain:</span>/<span class="font-mono text-xs">full:</span>/<span class="font-mono text-xs">geoip:</span>/<span class="font-mono text-xs">geosite:</span>.
                Ввод вида <span class="font-mono text-xs">yandex.ru/internet</span> тоже норм — берётся домен <span class="font-mono text-xs">yandex.ru</span>.
                Новая строка — <kbd class="px-1 rounded border border-slate-300 bg-slate-100 font-mono text-xs">Enter</kbd>.
                К <span class="font-mono text-xs">HAPP_DIRECT_SITES</span> список <strong>дописывается</strong>. Строка с <span class="font-mono text-xs">#</span> — комментарий.
            </p>
            @if (count($routingConfigSites) > 0)
                <p class="text-xs text-slate-500">
                    Уже из конфига: @foreach ($routingConfigSites as $t)<span class="font-mono bg-slate-100 px-1 rounded">{{ $t }}</span>@if (! $loop->last) · @endif @endforeach
                </p>
            @endif

            <div>
                <label for="routing_rules" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Список правил</label>
                <textarea
                    name="routing_rules"
                    id="routing_rules"
                    rows="10"
                    class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 font-mono text-sm focus:border-slate-400 focus:ring-slate-400"
                    placeholder="https://2ip.ru/&#10;sberbank.ru&#10;geoip:ru&#10;# комментарий"
                >{{ old('routing_rules', $routingRules) }}</textarea>
                @error('routing_rules')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            @if (count($routingPreviewSites) > 0 || count($routingPreviewIps) > 0)
                <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-xs text-slate-700 space-y-2">
                    <p class="font-bold text-slate-900">После сохранения (плюс конфиг)</p>
                    @if (count($routingPreviewSites) > 0)
                        <p><span class="font-semibold text-slate-600">DirectSites:</span>
                            @foreach ($routingPreviewSites as $s)<span class="font-mono mr-1">{{ $s }}</span>@if (! $loop->last) · @endif @endforeach
                        </p>
                    @endif
                    @if (count($routingPreviewIps) > 0)
                        <p><span class="font-semibold text-slate-600">DirectIp:</span>
                            @foreach ($routingPreviewIps as $s)<span class="font-mono mr-1">{{ $s }}</span>@if (! $loop->last) · @endif @endforeach
                        </p>
                    @endif
                </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-bold shadow-sm hover:bg-slate-800 min-h-[44px]">
                    Сохранить
                </button>
                <p class="text-xs text-slate-500 self-center">Очистите список и сохраните — сброс правил в БД.</p>
            </div>
        </form>
    </div>
@endsection

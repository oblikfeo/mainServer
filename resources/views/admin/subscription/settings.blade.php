@extends('layouts.admin')

@section('title', 'Подписка Happ')

@section('content')
    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← В меню
    </a>

    <div class="max-w-3xl w-full mx-auto space-y-10">
        <h1 class="text-xl sm:text-3xl font-bold text-slate-900 tracking-tight">Подписка Happ</h1>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-950 text-sm font-medium">
                {{ session('status') }}
            </div>
        @endif

        <form
            method="post"
            action="{{ route('admin.subscription.settings.update') }}"
            class="space-y-8"
        >
            @csrf

            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4">
                <h2 class="text-lg font-bold text-slate-900">Имя профиля</h2>
                <p class="text-sm text-slate-600 leading-relaxed">
                    До 25 символов. В тело подписки как <span class="font-mono text-xs">#profile-title</span>.
                    Пустое поле — из <span class="font-mono text-xs">SUB_PROFILE_TITLE</span> (по умолчанию: <span class="font-semibold">{{ $fromEnvDefault }}</span>).
                </p>
                <div>
                    <label for="profile_title" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Название</label>
                    <input
                        type="text"
                        name="profile_title"
                        id="profile_title"
                        value="{{ old('profile_title', $profileTitle) }}"
                        maxlength="25"
                        class="w-full max-w-xl rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400 min-h-[44px]"
                    >
                    @error('profile_title')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <section id="happ-routing" class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4">
                <h2 class="text-lg font-bold text-slate-900">Обход VPN (Happ routing)</h2>
                @if (! $happRoutingEnabled)
                    <p class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        В конфиге выключено (<span class="font-mono text-xs">HAPP_ROUTING_ENABLED=false</span>) — список сохраняется, но в подписку не попадает.
                    </p>
                @endif
                <p class="text-sm text-slate-600 leading-relaxed">
                    По одному пункту в строке. Поддерживаются: <span class="font-mono text-xs">https://…</span> (берётся хост),
                    домен вида <span class="font-mono text-xs">sberbank.ru</span>,
                    IPv4 / CIDR,
                    готовые правила <span class="font-mono text-xs">domain:</span> <span class="font-mono text-xs">full:</span> <span class="font-mono text-xs">keyword:</span> <span class="font-mono text-xs">geosite:</span> <span class="font-mono text-xs">geoip:</span>.
                    Строки с <span class="font-mono text-xs">#</span> в начале — комментарий.
                    К списку из <span class="font-mono text-xs">HAPP_DIRECT_SITES</span> в <span class="font-mono text-xs">.env</span> эти правила <strong>добавляются</strong> (не заменяют).
                </p>
                @if (count($routingConfigSites) > 0)
                    <p class="text-xs text-slate-500">
                        Сейчас из env/config: @foreach ($routingConfigSites as $t)<span class="font-mono bg-slate-100 px-1 rounded">{{ $t }}</span>@if (! $loop->last), @endif @endforeach
                    </p>
                @endif
                <div>
                    <label for="routing_rules" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Дополнительные правила Direct</label>
                    <textarea
                        name="routing_rules"
                        id="routing_rules"
                        rows="12"
                        class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 font-mono text-sm focus:border-slate-400 focus:ring-slate-400"
                        placeholder="https://2ip.ru/&#10;sberbank.ru&#10;full:www.gosuslugi.ru&#10;geoip:ru&#10;# комментарий"
                    >{{ old('routing_rules', $routingRules) }}</textarea>
                    @error('routing_rules')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                @if (count($routingPreviewSites) > 0 || count($routingPreviewIps) > 0)
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-xs text-slate-700 space-y-2">
                        <p class="font-bold text-slate-900">Предпросмотр (как уйдёт в Happ после слияния с конфигом)</p>
                        @if (count($routingPreviewSites) > 0)
                            <p><span class="font-semibold text-slate-600">DirectSites:</span>
                                @foreach ($routingPreviewSites as $s)<span class="font-mono mr-1">{{ $s }}</span>@if (! $loop->last) · @endif @endforeach
                            </p>
                        @endif
                        @if (count($routingPreviewIps) > 0)
                            <p><span class="font-semibold text-slate-600">+ DirectIp:</span>
                                @foreach ($routingPreviewIps as $s)<span class="font-mono mr-1">{{ $s }}</span>@if (! $loop->last) · @endif @endforeach
                            </p>
                        @endif
                    </div>
                @endif
            </section>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-bold shadow-sm hover:bg-slate-800 min-h-[44px]">
                    Сохранить всё
                </button>
                <p class="text-xs text-slate-500 self-center">Очистите «Название» или «Дополнительные правила» и сохраните — сброс на значения по умолчанию из конфига.</p>
            </div>
        </form>
    </div>
@endsection

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

            <section id="happ-profile" class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4">
                <h2 class="text-lg font-bold text-slate-900">Имя профиля</h2>
                <p class="text-sm text-slate-600">
                    До 25 символов → в подписку как <span class="font-mono text-xs">#profile-title</span>. Пусто = из конфига (<span class="font-semibold">{{ $fromEnvDefault }}</span>).
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
                <h2 class="text-lg font-bold text-slate-900">Обход Direct</h2>
                @if (! $happRoutingEnabled)
                    <p class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        Выключено в .env: <span class="font-mono text-xs">HAPP_ROUTING_ENABLED=false</span> — список сохраняется, в клиент не уходит.
                    </p>
                @endif
                <p class="text-sm text-slate-600">
                    Эти строки попадают в Happ как «без VPN». Одна строка = одно правило. К базе из <span class="font-mono text-xs">HAPP_DIRECT_SITES</span> <strong>дописываются</strong>, не заменяют.
                    Строка с <span class="font-mono text-xs">#</span> — комментарий.
                </p>
                @if (count($routingConfigSites) > 0)
                    <p class="text-xs text-slate-500">
                        Уже из конфига: @foreach ($routingConfigSites as $t)<span class="font-mono bg-slate-100 px-1 rounded">{{ $t }}</span>@if (! $loop->last) · @endif @endforeach
                    </p>
                @endif

                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/80 p-4 space-y-3">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Добавить в список</p>
                    <div class="flex flex-col sm:flex-row gap-2 sm:items-end">
                        <div class="flex-1 min-w-0">
                            <label for="rr_kind" class="sr-only">Тип</label>
                            <select id="rr_kind" class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 text-sm min-h-[44px]">
                                <option value="url">Ссылка (URL)</option>
                                <option value="domain">Домен</option>
                                <option value="ip">IP или сеть (CIDR)</option>
                                <option value="raw">Своя строка (как для Xray)</option>
                            </select>
                        </div>
                        <div class="flex-[2] min-w-0">
                            <label for="rr_val" class="sr-only">Значение</label>
                            <input
                                type="text"
                                id="rr_val"
                                class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 text-sm min-h-[44px] font-mono"
                                placeholder="Например bank.ru или 192.168.1.0/24"
                                autocomplete="off"
                            >
                        </div>
                        <button type="button" id="rr_add" class="rounded-xl border-2 border-slate-900 bg-white text-slate-900 px-4 py-2.5 text-sm font-bold min-h-[44px] hover:bg-slate-900 hover:text-white transition-colors shrink-0">
                            Вставить
                        </button>
                    </div>
                    <p class="text-xs text-slate-500">URL и домен обрабатываются так же, как если бы вы вписали их вручную в поле ниже. «Своя строка» — например <span class="font-mono">geosite:category-ads-all</span>.</p>
                </div>

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
                        <p class="font-bold text-slate-900">Как уйдёт из этой формы (плюс конфиг)</p>
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
            </section>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-bold shadow-sm hover:bg-slate-800 min-h-[44px]">
                    Сохранить
                </button>
                <p class="text-xs text-slate-500 self-center">Пустое имя или пустой список + сохранить = сброс к значениям из конфига.</p>
            </div>
        </form>
    </div>
    <script>
        (function () {
            var ta = document.getElementById('routing_rules');
            var kind = document.getElementById('rr_kind');
            var val = document.getElementById('rr_val');
            var btn = document.getElementById('rr_add');
            if (!ta || !kind || !val || !btn) return;
            btn.addEventListener('click', function () {
                var v = (val.value || '').trim();
                if (!v) return;
                var line;
                switch (kind.value) {
                    case 'url':
                        line = /^https?:\/\//i.test(v) ? v : 'https://' + v.replace(/^\/+/, '');
                        break;
                    case 'domain':
                    case 'ip':
                        line = v;
                        break;
                    case 'raw':
                    default:
                        line = v;
                        break;
                }
                var cur = ta.value.replace(/\s+$/, '');
                ta.value = (cur ? cur + '\n' : '') + line;
                val.value = '';
                val.focus();
            });
        })();
    </script>
@endsection

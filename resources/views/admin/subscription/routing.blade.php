@extends('layouts.admin')

@section('title', 'Обход VPN в Happ')

@section('content')
    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← В меню
    </a>

    <div class="max-w-3xl w-full mx-auto space-y-6">
        <div>
            <h1 class="text-xl sm:text-3xl font-bold text-slate-900 tracking-tight">Обход VPN в Happ</h1>
            <p class="mt-2 text-sm sm:text-base text-slate-600 leading-relaxed">
                Здесь настраиваются дополнительные правила <strong>Direct</strong> (трафик идёт мимо VPN, напрямую в интернет).
                Они попадают в подписку как профиль маршрутизации Happ — пользователю достаточно обновить подписку в приложении.
            </p>
        </div>

        @if (! $happRoutingEnabled)
            <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-amber-950 text-sm">
                <strong>Внимание:</strong> в конфиге выключено <code class="rounded bg-amber-100/80 px-1">HAPP_ROUTING_ENABLED</code>.
                Правила из .env и из этого экрана <strong>не попадут</strong> в выдачу подписки, пока не включите опцию и не сбросите кэш конфига на сервере.
            </div>
        @endif

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-950 text-sm font-medium">
                {{ session('status') }}
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:p-6 text-sm text-slate-700 space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Как это устроено</h2>
            <ol class="list-decimal list-inside space-y-2 leading-relaxed">
                <li>
                    <strong>База из сервера</strong> — список в <code class="rounded bg-white px-1 border border-slate-200">.env</code> как
                    <code class="rounded bg-white px-1 border border-slate-200">HAPP_DIRECT_SITES</code> (через запятую). Обычно там, например,
                    <code class="rounded bg-white px-1 border border-slate-200">geosite:category-ru</code> и точечные домены.
                </li>
                <li>
                    <strong>Дополнение из админки</strong> — многострочное поле ниже хранится в базе. Одна строка = одно правило.
                    Сохранение <strong>заменяет</strong> весь этот список целиком (не «дописывает в конец»).
                </li>
                <li>
                    <strong>В подписке</strong> база и дополнение объединяются и уходят в Happ как <code class="rounded bg-white px-1 border border-slate-200">DirectSites</code>
                    и при необходимости <code class="rounded bg-white px-1 border border-slate-200">DirectIp</code>.
                    Для правил <code class="rounded bg-white px-1 border border-slate-200">geosite:</code> / <code class="rounded bg-white px-1 border border-slate-200">geoip:</code>
                    клиент подкачивает geo-файлы (у вас это уже зашито в генератор подписки).
                </li>
            </ol>
            <p class="text-xs text-slate-500 pt-1">
                Лимит распознанных правил из этого поля: до {{ $maxRoutingEntries }} записей. Строки с <code class="rounded bg-slate-100 px-1">#</code> в начале — комментарии, в подписку не попадают.
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-900/5">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">База из .env (только просмотр)</h2>
            @if (empty($routingConfigSites))
                <p class="text-sm text-slate-500">Не задано или пустой массив <code class="rounded bg-slate-50 px-1">HAPP_DIRECT_SITES</code>.</p>
            @else
                <ul class="font-mono text-xs sm:text-sm text-slate-800 space-y-1 break-all">
                    @foreach ($routingConfigSites as $s)
                        <li>{{ $s }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <form
            method="post"
            action="{{ route('admin.subscription.routing.update') }}"
            class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4"
        >
            @csrf
            <div>
                <label for="routing_rules" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                    Дополнительные правила (хранятся в базе)
                </label>
                <textarea
                    name="routing_rules"
                    id="routing_rules"
                    rows="12"
                    class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 font-mono text-sm focus:border-slate-400 focus:ring-slate-400"
                >{{ old('routing_rules', $routingRules) }}</textarea>
                @error('routing_rules')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <details class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                <summary class="font-semibold text-slate-900 cursor-pointer select-none">Примеры строк</summary>
                <div class="mt-3 space-y-2 font-mono text-xs leading-relaxed">
                    <p><span class="text-slate-500"># домен целиком и поддомены (как в Xray)</span></p>
                    <p>domain:tbank.ru</p>
                    <p>full:api.example.com</p>
                    <p><span class="text-slate-500"># можно вставить URL — возьмём только хост</span></p>
                    <p>https://www.ozon.ru/path</p>
                    <p><span class="text-slate-500"># категория из geosite.dat (не дублируйте то, что уже в .env)</span></p>
                    <p>geosite:category-ru</p>
                    <p><span class="text-slate-500"># IPv4 / CIDR / geoip для DirectIp</span></p>
                    <p>geoip:ru</p>
                    <p>192.168.1.0/24</p>
                </div>
            </details>

            <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Предпросмотр итога для подписки</h3>
                <p class="text-xs text-slate-600">
                    Ниже — объединение <strong>базы из .env</strong> и <strong>распознанных</strong> строк из поля (как увидит Happ в <code class="rounded bg-white px-1 border border-slate-200">DirectSites</code>).
                </p>
                <div>
                    <div class="text-xs font-semibold text-slate-600 mb-1">DirectSites</div>
                    @if (empty($routingMergedSites))
                        <p class="text-xs text-slate-500">Пока пусто (проверьте .env и поле выше).</p>
                    @else
                        <ul class="font-mono text-xs text-slate-800 space-y-1 break-all max-h-48 overflow-y-auto">
                            @foreach ($routingMergedSites as $s)
                                <li>{{ $s }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-600 mb-1">DirectIp (только из этого поля: IPv4, CIDR, geoip:…)</div>
                    @if (empty($routingDirectIpFromAdmin))
                        <p class="text-xs text-slate-500">Из админки не задано.</p>
                    @else
                        <ul class="font-mono text-xs text-slate-800 space-y-1 break-all">
                            @foreach ($routingDirectIpFromAdmin as $s)
                                <li>{{ $s }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <p class="text-[11px] text-slate-500 mt-2">
                        К ним в клиенте добавляются служебные частные сети (RFC1918 и т.д.) — это делает генератор подписки, в поле указывать не нужно.
                    </p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-bold shadow-sm hover:bg-slate-800 min-h-[44px]">
                    Сохранить
                </button>
                <p class="text-xs text-slate-500 self-center">
                    Очистите поле и сохраните, чтобы удалить все доп. правила из базы.
                </p>
            </div>
        </form>

        <p class="text-xs text-slate-500">
            Документация Happ:
            <a href="https://www.happ.su/main/dev-docs/routing" class="text-slate-700 underline underline-offset-2 font-medium" target="_blank" rel="noopener noreferrer">routing</a>.
        </p>
    </div>
@endsection

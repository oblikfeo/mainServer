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
                <strong>По умолчанию</strong> сервер <strong>не включает</strong> маршрутизацию Happ: в подписку уходит только
                <code class="rounded bg-slate-100 px-1 text-sm">happ://routing/off</code> (см.
                <a href="https://www.happ.su/main/dev-docs/routing" class="underline underline-offset-2" target="_blank" rel="noopener noreferrer">документацию Happ</a>),
                чтобы приложение отключило профили с geo-файлами. Списки Direct ниже используются только если в .env включён
                <code class="rounded bg-slate-100 px-1 text-sm">HAPP_ROUTING_ENABLED=true</code>.
            </p>
        </div>

        @if (! $happRoutingEnabled)
            <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sky-950 text-sm leading-relaxed">
                <strong>Режим без маршрутизации Happ.</strong> В выдаче подписки — первая строка
                <code class="rounded bg-white/80 px-1">happ://routing/off</code>; правила из .env и из поля ниже в клиент <strong>не отправляются</strong>.
                Чтобы снова отдавать профиль с DirectSites, задайте <code class="rounded bg-white/80 px-1">HAPP_ROUTING_ENABLED=true</code> в .env и выполните на сервере
                <code class="rounded bg-white/80 px-1">php artisan config:clear</code>.
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
                    <strong>Geo-файлы</strong> — URL на <code class="rounded bg-white px-1 border border-slate-200">geoip.dat</code>/<code class="rounded bg-white px-1 border border-slate-200">geosite.dat</code> задаются в
                    <code class="rounded bg-white px-1 border border-slate-200">.env</code> (<code class="rounded bg-white px-1 border border-slate-200">HAPP_GEOIP_URL</code>/<code class="rounded bg-white px-1 border border-slate-200">HAPP_GEOSITE_URL</code>).
                    По умолчанию — самый популярный набор Loyalsoldier
                    (<a class="underline underline-offset-2" target="_blank" rel="noopener noreferrer" href="https://github.com/Loyalsoldier/v2ray-rules-dat">v2ray-rules-dat</a>):
                    <code class="rounded bg-white px-1 border border-slate-200">geosite:category-ru</code>, <code class="rounded bg-white px-1 border border-slate-200">geoip:ru</code>,
                    <code class="rounded bg-white px-1 border border-slate-200">geosite:category-ads-all</code> и т.д. Пустая строка → Happ не качает <code class="rounded bg-white px-1 border border-slate-200">.dat</code>,
                    а правила <code class="rounded bg-white px-1 border border-slate-200">geosite:</code>/<code class="rounded bg-white px-1 border border-slate-200">geoip:</code> отбрасываются.
                </li>
                <li>
                    <strong>База из сервера</strong> — списки в <code class="rounded bg-white px-1 border border-slate-200">.env</code>: <code class="rounded bg-white px-1 border border-slate-200">HAPP_DIRECT_SITES</code> (домены/geosite:),
                    <code class="rounded bg-white px-1 border border-slate-200">HAPP_DIRECT_IP</code> (CIDR/geoip:),
                    <code class="rounded bg-white px-1 border border-slate-200">HAPP_BLOCK_SITES</code> (что блокировать). Через запятую.
                </li>
                <li>
                    <strong>Дополнение из админки</strong> — многострочное поле ниже хранится в базе. Одна строка = одно правило.
                    Сохранение <strong>заменяет</strong> весь этот список целиком. Можно вписывать <code class="rounded bg-white px-1 border border-slate-200">geosite:</code>/<code class="rounded bg-white px-1 border border-slate-200">geoip:</code> —
                    они попадут в Happ, если соответствующий URL не пуст.
                </li>
                <li>
                    <strong>В подписке</strong> база и дополнение объединяются и уходят в Happ как <code class="rounded bg-white px-1 border border-slate-200">DirectSites</code>,
                    <code class="rounded bg-white px-1 border border-slate-200">DirectIp</code> и <code class="rounded bg-white px-1 border border-slate-200">BlockSites</code>.
                </li>
            </ol>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Geo-файлы из .env</h2>
            <div class="grid grid-cols-1 gap-3 text-sm">
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">HAPP_GEOIP_URL</div>
                    @if (! empty($happGeoipUrl))
                        <a href="{{ $happGeoipUrl }}" target="_blank" rel="noopener noreferrer" class="font-mono text-xs sm:text-sm text-slate-800 break-all underline underline-offset-2">{{ $happGeoipUrl }}</a>
                    @else
                        <p class="text-sm text-slate-500">Пусто — клиент не скачает <code class="rounded bg-slate-50 px-1">geoip.dat</code>.</p>
                    @endif
                </div>
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">HAPP_GEOSITE_URL</div>
                    @if (! empty($happGeositeUrl))
                        <a href="{{ $happGeositeUrl }}" target="_blank" rel="noopener noreferrer" class="font-mono text-xs sm:text-sm text-slate-800 break-all underline underline-offset-2">{{ $happGeositeUrl }}</a>
                    @else
                        <p class="text-sm text-slate-500">Пусто — клиент не скачает <code class="rounded bg-slate-50 px-1">geosite.dat</code>.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">База из .env (только просмотр)</h2>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">HAPP_DIRECT_SITES</div>
                @if (empty($routingConfigSites))
                    <p class="text-sm text-slate-500">Не задано.</p>
                @else
                    <ul class="font-mono text-xs sm:text-sm text-slate-800 space-y-1 break-all">
                        @foreach ($routingConfigSites as $s)
                            <li>{{ $s }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">HAPP_DIRECT_IP</div>
                @if (empty($routingConfigIp))
                    <p class="text-sm text-slate-500">Не задано.</p>
                @else
                    <ul class="font-mono text-xs sm:text-sm text-slate-800 space-y-1 break-all">
                        @foreach ($routingConfigIp as $s)
                            <li>{{ $s }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">HAPP_BLOCK_SITES</div>
                @if (empty($routingConfigBlockSites))
                    <p class="text-sm text-slate-500">Не задано — ничего не блокируется.</p>
                @else
                    <ul class="font-mono text-xs sm:text-sm text-slate-800 space-y-1 break-all">
                        @foreach ($routingConfigBlockSites as $s)
                            <li>{{ $s }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
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
                    <p><span class="text-slate-500"># geosite/geoip из geosite.dat/geoip.dat (работают, если задан URL в .env)</span></p>
                    <p>geosite:category-ru</p>
                    <p>geoip:ru</p>
                    <p><span class="text-slate-500"># обычный CIDR или одиночный IPv4</span></p>
                    <p>192.168.1.0/24</p>
                </div>
            </details>

            <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Предпросмотр итога для подписки</h3>
                <p class="text-xs text-slate-600">
                    Объединение <strong>базы из .env</strong> и правил из поля. В URI-подписке этот список не показывается отдельным конфигом: он внутри строки
                    <code class="rounded bg-white px-1 border">happ://routing/…</code> (base64).
                    <strong>geosite:/geoip:</strong> оставляются в профиле, только когда заданы <code class="rounded bg-white px-1 border">HAPP_GEOSITE_URL</code>/<code class="rounded bg-white px-1 border">HAPP_GEOIP_URL</code>.
                </p>
                <div>
                    <div class="text-xs font-semibold text-slate-600 mb-1">DirectSites (домены / geosite:) — как уйдёт в Happ</div>
                    @if (empty($routingHappProfileSites))
                        <p class="text-xs text-slate-500">Пусто.</p>
                    @else
                        <ul class="font-mono text-xs text-slate-800 space-y-1 break-all max-h-48 overflow-y-auto">
                            @foreach ($routingHappProfileSites as $s)
                                <li>{{ $s }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if (! empty($routingMergedSites) && count($routingHappProfileSites) !== count($routingMergedSites))
                        <p class="text-[11px] text-amber-800 mt-2">
                            Часть правил отброшена (например <code class="rounded bg-amber-100 px-1">geosite:</code> без HAPP_GEOSITE_URL).
                            В сводном объединении было {{ count($routingMergedSites) }} строк.
                        </p>
                    @endif
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-600 mb-1">DirectIp (CIDR/geoip:) — как уйдёт в Happ</div>
                    @if (empty($routingHappProfileIpExtras))
                        <p class="text-xs text-slate-500">Пусто (только служебные частные сети ниже).</p>
                    @else
                        <ul class="font-mono text-xs text-slate-800 space-y-1 break-all">
                            @foreach ($routingHappProfileIpExtras as $s)
                                <li>{{ $s }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if (! empty($routingMergedIp) && count($routingHappProfileIpExtras) !== count($routingMergedIp))
                        <p class="text-[11px] text-amber-800 mt-2">
                            Часть строк отброшена (например <code class="rounded bg-amber-100 px-1">geoip:</code> без HAPP_GEOIP_URL).
                            В сводном объединении было {{ count($routingMergedIp) }} строк.
                        </p>
                    @endif
                    <p class="text-[11px] text-slate-500 mt-2">
                        К ним в клиенте добавляются служебные частные сети (RFC1918 и т.д.) и DoH-bootstrap <code class="rounded bg-white px-1 border">1.1.1.1/32</code> — это делает генератор подписки.
                    </p>
                </div>
                @if (! empty($routingMergedBlockSites))
                    <div>
                        <div class="text-xs font-semibold text-slate-600 mb-1">BlockSites (что блокируется)</div>
                        <ul class="font-mono text-xs text-slate-800 space-y-1 break-all">
                            @foreach ($routingMergedBlockSites as $s)
                                <li>{{ $s }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
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

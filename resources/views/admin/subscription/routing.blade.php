@extends('layouts.admin')

@section('title', 'Обход VPN')

@section('content')
    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← В меню
    </a>

    <div class="max-w-3xl w-full mx-auto space-y-6">
        <div>
            <h1 class="text-xl sm:text-3xl font-bold text-slate-900 tracking-tight">Обход VPN</h1>
            <p class="mt-2 text-sm sm:text-base text-slate-600 leading-relaxed">
                Сайты и адреса, которые клиент открывает напрямую, без VPN. Российские сервисы и реклама уже настроены на сервере —
                сюда добавляйте только то, чего не хватает.
            </p>
        </div>

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
            <div>
                <label for="routing_rules" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                    Дополнительные сайты и адреса
                </label>
                <textarea
                    name="routing_rules"
                    id="routing_rules"
                    rows="14"
                    placeholder="domain:tbank.ru&#10;https://www.ozon.ru"
                    class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 font-mono text-sm focus:border-slate-400 focus:ring-slate-400"
                >{{ old('routing_rules', $routingRules) }}</textarea>
                @error('routing_rules')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-slate-500">Одна строка — одно правило. Сохранение заменяет весь список целиком.</p>
            </div>

            <details class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                <summary class="font-semibold text-slate-900 cursor-pointer select-none">Как писать правила</summary>
                <div class="mt-3 space-y-2 font-mono text-xs leading-relaxed">
                    <p><span class="text-slate-500"># домен и все поддомены</span></p>
                    <p>domain:tbank.ru</p>
                    <p><span class="text-slate-500"># можно вставить ссылку — возьмём только адрес сайта</span></p>
                    <p>https://www.ozon.ru/catalog</p>
                    <p><span class="text-slate-500"># конкретный IP или подсеть</span></p>
                    <p>192.168.1.0/24</p>
                </div>
            </details>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-bold shadow-sm hover:bg-slate-800 min-h-[44px]">
                    Сохранить
                </button>
                <p class="text-xs text-slate-500 self-center">
                    Клиентам нужно обновить подписку в Happ после сохранения.
                </p>
            </div>
        </form>

        @if (! empty($routingActiveSites) || ! empty($routingActiveIp))
            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-900/5">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Что сейчас уходит в подписку</h2>
                @if (! empty($routingActiveSites))
                    <p class="text-xs text-slate-500 mb-2">Сайты ({{ count($routingActiveSites) }})</p>
                    <ul class="font-mono text-xs text-slate-800 space-y-1 break-all max-h-56 overflow-y-auto">
                        @foreach ($routingActiveSites as $s)
                            <li>{{ $s }}</li>
                        @endforeach
                    </ul>
                @endif
                @if (! empty($routingActiveIp))
                    <p class="text-xs text-slate-500 mb-2 {{ ! empty($routingActiveSites) ? 'mt-4' : '' }}">IP и подсети ({{ count($routingActiveIp) }})</p>
                    <ul class="font-mono text-xs text-slate-800 space-y-1 break-all">
                        @foreach ($routingActiveIp as $s)
                            <li>{{ $s }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </div>
@endsection

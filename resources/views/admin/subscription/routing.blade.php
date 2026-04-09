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
            <div>
                <label for="routing_rules" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Список правил</label>
                <textarea
                    name="routing_rules"
                    id="routing_rules"
                    rows="10"
                    class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 font-mono text-sm focus:border-slate-400 focus:ring-slate-400"
                    placeholder=""
                ></textarea>
                @error('routing_rules')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <div class="rounded-xl border border-slate-100 bg-white px-4 py-3 text-xs text-slate-700 space-y-2">
                    <p class="font-bold text-slate-900">Что в списке</p>
                    @if (empty($routingRawLines))
                        <p class="text-slate-500">Пусто</p>
                    @else
                        <div class="font-mono break-words">
                            @foreach ($routingRawLines as $s)
                                <div>{{ $s }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-xs text-slate-700 space-y-2">
                    <p class="font-bold text-slate-900">Что уйдёт в Happ</p>
                    <div class="space-y-2">
                        <div>
                            <div class="font-semibold text-slate-600">Сайты</div>
                            @if (! empty($routingMergedSitesDisplay))
                                <div class="font-mono break-words">
                                    @foreach ($routingMergedSitesDisplay as $s)
                                        <div>{{ $s }}</div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-slate-500">Пусто</div>
                            @endif
                        </div>
                        <div>
                            <div class="font-semibold text-slate-600">IP</div>
                            @if (! empty($routingDirectIpDisplay))
                                <div class="font-mono break-words">
                                    @foreach ($routingDirectIpDisplay as $s)
                                        <div>{{ $s }}</div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-slate-500">Пусто</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-bold shadow-sm hover:bg-slate-800 min-h-[44px]">
                    Сохранить
                </button>
            </div>
        </form>
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'Панель')

@section('content')
    <div class="mb-10">
        <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 tracking-tight mb-3">
            Обзор
        </h1>
        <p class="text-xl text-slate-600 max-w-3xl">
            Сводка по связкам и узлам. Проверка — TCP до указанного порта с головного сервера.
        </p>
    </div>

    {{-- Блок: статистика серверов (связки) --}}
    <section class="mb-12" aria-labelledby="servers-stats-heading">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
            <h2 id="servers-stats-heading" class="text-3xl sm:text-4xl font-bold text-slate-900">
                Статистика серверов
            </h2>
            <div class="flex items-baseline gap-3">
                <span class="text-5xl sm:text-6xl font-bold tabular-nums text-emerald-600">{{ $onlineCount }}</span>
                <span class="text-2xl text-slate-400 font-medium">/</span>
                <span class="text-3xl font-semibold text-slate-700 tabular-nums">{{ $totalBundles }}</span>
                <span class="text-lg text-slate-500">онлайн</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
            @foreach ($bundles as $bundle)
                <article
                    class="rounded-2xl border-2 p-8 sm:p-10 shadow-md transition-shadow hover:shadow-lg
                        {{ $bundle['online'] ? 'border-emerald-200 bg-white' : 'border-rose-200 bg-rose-50/40' }}"
                >
                    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-1">
                                Связка
                            </p>
                            <h3 class="text-3xl sm:text-4xl font-bold text-slate-900">
                                {{ $bundle['name'] }}
                            </h3>
                            <p class="mt-2 text-lg text-slate-600">
                                {{ $bundle['subtitle'] ?? '' }}
                            </p>
                        </div>
                        <span
                            class="inline-flex items-center px-4 py-2 rounded-xl text-base font-semibold shrink-0
                                {{ $bundle['online'] ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}"
                        >
                            {{ $bundle['online'] ? 'Доступен' : 'Нет ответа' }}
                        </span>
                    </div>

                    <dl class="space-y-5 text-lg">
                        <div>
                            <dt class="text-sm font-medium text-slate-500 uppercase tracking-wide">IP</dt>
                            <dd class="mt-1 font-mono text-2xl sm:text-3xl font-semibold text-slate-900 break-all">
                                {{ $bundle['ip'] }}
                            </dd>
                        </div>
                        <div class="flex flex-wrap gap-8">
                            <div>
                                <dt class="text-sm font-medium text-slate-500 uppercase tracking-wide">SSH</dt>
                                <dd class="mt-1 text-xl font-medium text-slate-800">
                                    {{ $bundle['ssh_user'] ?? '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-slate-500 uppercase tracking-wide">Проверка TCP</dt>
                                <dd class="mt-1 text-xl font-mono font-medium text-slate-800">
                                    :{{ $bundle['check_port'] }}
                                </dd>
                            </div>
                        </div>
                    </dl>
                </article>
            @endforeach
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-8 sm:p-10 shadow-sm">
        <h2 class="text-2xl font-bold text-slate-900 mb-4">Дальше</h2>
        <p class="text-lg text-slate-600 max-w-2xl">
            Здесь появятся выпуск ключей и операции по inbound&apos;ам. Связки настраиваются в
            <code class="bg-slate-100 px-2 py-0.5 rounded text-base">config/links.php</code>.
        </p>
    </section>
@endsection

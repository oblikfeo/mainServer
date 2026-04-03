@extends('layouts.admin')

@section('title', 'Статус серверов')

@section('content')
    <div class="mb-8">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-base font-medium text-slate-600 hover:text-slate-900 mb-6">
            ← К разделам
        </a>

        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-10">
            <h1 class="text-3xl sm:text-4xl font-bold text-slate-900">
                Статус серверов
            </h1>
            <div class="flex items-baseline gap-2 flex-wrap">
                <span class="text-slate-600 text-lg">Онлайн:</span>
                <span class="text-4xl sm:text-5xl font-bold tabular-nums text-emerald-600">{{ $onlineCount }}</span>
                <span class="text-2xl text-slate-400 font-medium">/</span>
                <span class="text-3xl font-semibold text-slate-700 tabular-nums">{{ $totalBundles }}</span>
            </div>
        </div>
        <p class="text-slate-600 text-base max-w-3xl mb-2">
            Данные проверяются сейчас: с головного сервера открывается TCP-сессия к IP и порту из настроек каждой связки (см. <code class="bg-slate-200/80 px-1.5 py-0.5 rounded text-sm">config/links.php</code>). Если порт закрыт файрволом или сервис не слушает — показывается «Нет ответа».
        </p>
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
                        <h2 class="text-3xl sm:text-4xl font-bold text-slate-900">
                            {{ $bundle['name'] }}
                        </h2>
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
@endsection

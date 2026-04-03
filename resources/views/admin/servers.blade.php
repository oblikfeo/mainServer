@extends('layouts.admin')

@section('title', '')

@section('content')
    <a href="{{ route('admin.dashboard') }}" class="inline-block text-slate-600 hover:text-slate-900 mb-8 text-lg font-medium">
        ←
    </a>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10">
        <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
            <div class="text-4xl sm:text-5xl font-bold tabular-nums text-slate-900">{{ $totalKeys }}</div>
            <div class="text-slate-500 text-sm font-medium mt-1">ключей выдано</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
            <div class="text-4xl sm:text-5xl font-bold tabular-nums text-emerald-600">{{ $onlineCount }}</div>
            <div class="text-slate-500 text-sm font-medium mt-1">узлов онлайн</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-6 shadow-sm">
            <div class="text-4xl sm:text-5xl font-bold tabular-nums text-slate-700">{{ $totalBundles }}</div>
            <div class="text-slate-500 text-sm font-medium mt-1">узлов всего</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach ($bundles as $bundle)
            <article
                class="rounded-2xl border-2 p-8 shadow-md
                    {{ $bundle['online'] ? 'border-emerald-200 bg-white' : 'border-rose-300 bg-rose-50/50' }}"
            >
                <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
                    <div>
                        <h2 class="text-3xl sm:text-4xl font-bold text-slate-900">
                            {{ $bundle['name'] }}
                        </h2>
                        @if (!empty($bundle['subtitle']))
                            <p class="mt-1 text-lg text-slate-600">{{ $bundle['subtitle'] }}</p>
                        @endif
                    </div>
                    <span
                        class="inline-flex items-center px-4 py-2 rounded-xl text-base font-semibold shrink-0
                            {{ $bundle['online'] ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}"
                    >
                        {{ $bundle['online'] ? 'онлайн' : 'офлайн' }}
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 col-span-2 sm:col-span-1">
                        <div class="text-2xl font-bold tabular-nums text-slate-900">{{ $bundle['keys_count'] }}</div>
                        <div class="text-xs text-slate-500 font-medium uppercase tracking-wide mt-1">ключей на узле</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 col-span-2 sm:col-span-1">
                        <div class="text-2xl font-bold tabular-nums text-slate-400">—</div>
                        <div class="text-xs text-slate-500 font-medium uppercase tracking-wide mt-1">нагрузка</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 col-span-2 sm:col-span-1">
                        <div class="text-2xl font-bold tabular-nums text-slate-400">—</div>
                        <div class="text-xs text-slate-500 font-medium uppercase tracking-wide mt-1">трафик / 30 дн</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 col-span-2 sm:col-span-1">
                        <div class="text-2xl font-bold tabular-nums text-slate-400">—</div>
                        <div class="text-xs text-slate-500 font-medium uppercase tracking-wide mt-1">CPU</div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endsection

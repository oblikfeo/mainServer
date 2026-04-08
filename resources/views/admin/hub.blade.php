@extends('layouts.admin')

@section('title', '')

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6">
        <a
            href="{{ route('admin.subscription.settings') }}"
            class="group flex items-center justify-center rounded-2xl border-2 border-slate-200 bg-white p-6 sm:p-10 shadow-sm hover:border-slate-900 hover:shadow-lg transition-all min-h-[120px] sm:min-h-[140px] active:scale-[0.99]"
        >
            <span class="text-lg sm:text-2xl xl:text-3xl font-bold text-slate-900 text-center leading-snug px-2 group-hover:text-slate-700">
                Happ: имя и обход
            </span>
        </a>
        <a
            href="{{ route('admin.subscription.settings') }}#happ-routing"
            class="group flex items-center justify-center rounded-2xl border-2 border-slate-200 bg-white p-6 sm:p-10 shadow-sm hover:border-slate-900 hover:shadow-lg transition-all min-h-[120px] sm:min-h-[140px] active:scale-[0.99]"
        >
            <span class="text-lg sm:text-2xl xl:text-3xl font-bold text-slate-900 text-center leading-snug px-2 group-hover:text-slate-700">
                Правила Direct
            </span>
        </a>
        <a
            href="{{ route('admin.servers') }}"
            class="group flex items-center justify-center rounded-2xl border-2 border-slate-200 bg-white p-6 sm:p-10 shadow-sm hover:border-slate-900 hover:shadow-lg transition-all min-h-[120px] sm:min-h-[140px] active:scale-[0.99]"
        >
            <span class="text-lg sm:text-2xl xl:text-3xl font-bold text-slate-900 text-center leading-snug px-2 group-hover:text-slate-700">
                Статус серверов
            </span>
        </a>

        <a
            href="{{ route('admin.subscription.create') }}"
            class="group flex items-center justify-center rounded-2xl border-2 border-slate-200 bg-white p-6 sm:p-10 shadow-sm hover:border-slate-900 hover:shadow-lg transition-all min-h-[120px] sm:min-h-[140px] active:scale-[0.99]"
        >
            <span class="text-lg sm:text-2xl xl:text-3xl font-bold text-slate-900 text-center leading-snug px-2 group-hover:text-slate-700">
                Создание подписки
            </span>
        </a>

        <a
            href="{{ route('admin.report') }}"
            class="group flex items-center justify-center rounded-2xl border-2 border-slate-200 bg-white p-6 sm:p-10 shadow-sm hover:border-slate-900 hover:shadow-lg transition-all min-h-[120px] sm:min-h-[140px] active:scale-[0.99]"
        >
            <span class="text-lg sm:text-2xl xl:text-3xl font-bold text-slate-900 text-center leading-snug px-2 group-hover:text-slate-700">
                Отчёт
            </span>
        </a>
    </div>
@endsection

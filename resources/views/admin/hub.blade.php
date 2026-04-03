@extends('layouts.admin')

@section('title', 'Разделы')

@section('content')
    <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 tracking-tight mb-2">
        Разделы
    </h1>
    <p class="text-lg text-slate-600 mb-10 max-w-2xl">
        Выберите раздел
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
        <a
            href="{{ route('admin.servers') }}"
            class="group flex flex-col rounded-2xl border-2 border-slate-200 bg-white p-8 shadow-sm hover:border-slate-900 hover:shadow-lg transition-all min-h-[180px]"
        >
            <span class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-3">Мониторинг</span>
            <span class="text-2xl sm:text-3xl font-bold text-slate-900 group-hover:text-slate-700 mb-2">
                Статус серверов
            </span>
            <span class="text-slate-600 text-base mt-auto">
                Связки, TCP с этого хоста
            </span>
        </a>

        <a
            href="{{ route('admin.subscription.create') }}"
            class="group flex flex-col rounded-2xl border-2 border-slate-200 bg-white p-8 shadow-sm hover:border-slate-900 hover:shadow-lg transition-all min-h-[180px]"
        >
            <span class="text-sm font-semibold uppercase tracking-wider text-slate-500 mb-3">Клиенты</span>
            <span class="text-2xl sm:text-3xl font-bold text-slate-900 group-hover:text-slate-700 mb-2">
                Создание подписки
            </span>
            <span class="text-slate-600 text-base mt-auto">
                Выпуск доступа
            </span>
        </a>
    </div>
@endsection

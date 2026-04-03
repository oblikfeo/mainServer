@extends('layouts.admin')

@section('title', '')

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
        <a
            href="{{ route('admin.servers') }}"
            class="group flex items-center justify-center rounded-2xl border-2 border-slate-200 bg-white p-10 shadow-sm hover:border-slate-900 hover:shadow-lg transition-all min-h-[140px]"
        >
            <span class="text-2xl sm:text-3xl font-bold text-slate-900 text-center group-hover:text-slate-700">
                Статус серверов
            </span>
        </a>

        <a
            href="{{ route('admin.subscription.create') }}"
            class="group flex items-center justify-center rounded-2xl border-2 border-slate-200 bg-white p-10 shadow-sm hover:border-slate-900 hover:shadow-lg transition-all min-h-[140px]"
        >
            <span class="text-2xl sm:text-3xl font-bold text-slate-900 text-center group-hover:text-slate-700">
                Создание подписки
            </span>
        </a>
    </div>
@endsection

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $__pageTitle = trim($__env->yieldContent('title'));
        @endphp
        <title>{{ $__pageTitle !== '' ? $__pageTitle.' — ' : '' }}Надежда</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased min-h-screen bg-slate-100">
        <header class="bg-white border-b border-slate-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 py-5 flex flex-wrap items-center justify-between gap-4">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 group">
                    <span class="flex h-11 w-11 sm:h-12 sm:w-12 items-center justify-center rounded-2xl bg-slate-900 text-white text-lg sm:text-xl font-bold shadow-md group-hover:bg-slate-800 transition-colors shrink-0" aria-hidden="true">
                        Н
                    </span>
                    <span class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                        Надежда
                    </span>
                </a>
                <nav class="flex flex-wrap items-center gap-1 sm:gap-2 order-3 sm:order-none w-full sm:w-auto justify-center sm:justify-end text-sm sm:text-base font-medium" aria-label="Админ-меню">
                    <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 {{ request()->routeIs('admin.dashboard') ? 'text-slate-900 bg-slate-100' : '' }}">Главная</a>
                    <a href="{{ route('admin.servers') }}" class="px-3 py-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 {{ request()->routeIs('admin.servers') ? 'text-slate-900 bg-slate-100' : '' }}">Серверы</a>
                    <a href="{{ route('admin.subscription.create') }}" class="px-3 py-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 {{ request()->routeIs('admin.subscription.*') ? 'text-slate-900 bg-slate-100' : '' }}">Подписка</a>
                    <a href="{{ route('admin.report') }}" class="px-3 py-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 {{ request()->routeIs('admin.report') ? 'text-slate-900 bg-slate-100' : '' }}">Отчёт</a>
                </nav>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="text-base font-medium text-slate-600 hover:text-slate-900 px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors">
                        Выйти
                    </button>
                </form>
            </div>
        </header>
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 py-8 sm:py-10">
            @yield('content')
        </main>
    </body>
</html>

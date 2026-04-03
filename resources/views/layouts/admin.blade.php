<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Админка') — Надежда</title>
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

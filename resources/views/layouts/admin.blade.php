<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', 'Админка') — {{ config('app.name', 'VPN Hub') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased min-h-screen bg-slate-100">
        <header class="bg-white border-b border-slate-200 shadow-sm">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-10 py-5 flex flex-wrap items-center justify-between gap-4">
                <a href="{{ route('admin.dashboard') }}" class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                    VPN Hub <span class="text-slate-400 font-semibold text-xl sm:text-2xl">· админка</span>
                </a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="text-base font-medium text-slate-600 hover:text-slate-900 px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors">
                        Выйти
                    </button>
                </form>
            </div>
        </header>
        <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-10 py-10 sm:py-12">
            @yield('content')
        </main>
    </body>
</html>

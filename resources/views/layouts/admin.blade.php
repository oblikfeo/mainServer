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
        @include('partials.favicon')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased min-h-screen bg-slate-100">
        <header class="bg-white border-b border-slate-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-10 py-3 sm:py-5 flex flex-wrap items-center justify-between gap-3 sm:gap-4">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 sm:gap-3 group min-w-0">
                    <span class="flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-2xl bg-slate-900 text-white text-base sm:text-xl font-bold shadow-md group-hover:bg-slate-800 transition-colors shrink-0" aria-hidden="true">
                        Н
                    </span>
                    <span class="text-xl sm:text-2xl lg:text-3xl font-bold tracking-tight text-slate-900 truncate">
                        Надежда
                    </span>
                </a>
                <form method="POST" action="{{ route('admin.logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="text-sm sm:text-base font-medium text-slate-600 hover:text-slate-900 px-3 sm:px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors min-h-[44px] sm:min-h-0">
                        Выйти
                    </button>
                </form>
            </div>
        </header>
        <main class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-10 py-5 sm:py-8 lg:py-10 pb-8 sm:pb-10">
            @yield('content')
        </main>
    </body>
</html>

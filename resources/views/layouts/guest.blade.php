<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('marketing.brand_name', 'Надежда').' — вход' }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased bg-gradient-to-b from-slate-100 to-slate-200/80 min-h-screen">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-8 pb-10 sm:pt-0 px-4">
            <div>
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 text-white px-5 py-2 text-sm font-bold tracking-tight hover:bg-slate-800 transition-colors">
                    {{ config('marketing.brand_name', 'Надежда') }}
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-8 px-6 py-6 bg-white shadow-xl shadow-slate-900/10 overflow-hidden sm:rounded-2xl border border-slate-200/80 ring-1 ring-slate-900/5">
                {{ $slot }}
            </div>
            <p class="mt-6 text-center text-xs text-slate-500">
                <a href="{{ url('/') }}" class="font-semibold text-slate-600 hover:text-slate-900">На главную</a>
            </p>
        </div>
    </body>
</html>

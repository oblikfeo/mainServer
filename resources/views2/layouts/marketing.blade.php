<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @include('partials.yandex-metrika')
        <title>@yield('title', config('marketing.brand_name', 'Надежда'))</title>
        <meta name="description" content="@yield('meta_description', '')">
        <meta property="og:title" content="@yield('title', config('marketing.brand_name', 'Надежда'))">
        <meta property="og:description" content="@yield('meta_description', '')">
        <meta property="og:type" content="website">
        @include('partials.favicon')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Space+Grotesk:wght@500;600;700&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="font-sans text-slate-900 antialiased bg-slate-50">
        @yield('content')
        @stack('scripts')
    </body>
</html>

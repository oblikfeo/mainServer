<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', config('marketing.brand_name', 'Надежда'))</title>
        <meta name="description" content="@yield('meta_description', '')">
        <meta property="og:title" content="@yield('title', config('marketing.brand_name', 'Надежда'))">
        <meta property="og:description" content="@yield('meta_description', '')">
        <meta property="og:type" content="website">
        @include('partials.favicon')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>
    <body class="font-sans text-slate-900 antialiased bg-slate-50">
        @yield('content')
    </body>
</html>

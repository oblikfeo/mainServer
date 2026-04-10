<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $brand = config('marketing.brand_name', 'Надежда');
            $rn = request()->route()?->getName();
            $pageTitle = match ($rn) {
                'login' => 'Вход',
                'register' => 'Регистрация',
                'password.request' => 'Сброс пароля',
                'password.reset' => 'Новый пароль',
                'verification.notice' => 'Подтвердите email',
                'password.confirm' => 'Подтверждение',
                default => 'Аккаунт',
            };
        @endphp
        <title>{{ $brand }} — {{ $pageTitle }}</title>
        @include('partials.favicon')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.lp-f1-styles')
    </head>
    <body class="m-0 p-0">
        <div class="lp-f1 lp-f1-body lp-f1-auth">
            <div class="lp-container lp-container--narrow">
                <div class="lp-header">
                    <a href="{{ url('/') }}" class="lp-logo lp-cabinet-header__brand">{{ $brand }}</a>
                    @if (in_array($rn, ['register', 'password.request', 'password.reset'], true))
                        <a href="{{ route('login') }}" class="lp-login-btn">Вход</a>
                    @elseif ($rn === 'login')
                        <a href="{{ route('register') }}" class="lp-login-btn">Регистрация</a>
                    @else
                        <a href="{{ route('login') }}" class="lp-login-btn">Вход</a>
                    @endif
                </div>

                <div class="lp-auth-panel">
                    {{ $slot }}
                </div>

                <div class="lp-auth-footer">
                    <a href="{{ url('/') }}">На главную</a>
                </div>
            </div>
        </div>
    </body>
</html>

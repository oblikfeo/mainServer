@props([])

@php
    $brand = config('marketing.brand_name', 'Надежда');
    $supportTgUrl = config('marketing.telegram_support_url', 'https://t.me/nadezhda_tehsup');
    $newsTgUrl = config('marketing.telegram_url', $supportTgUrl);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $brand }} — личный кабинет</title>
        @include('partials.favicon')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.lp-f1-styles')
    </head>
    <body class="m-0 p-0">
        <div class="lp-f1 lp-f1-body lp-f1-cabinet" x-data="{ mobileNav: false }">
            <div class="lp-container lp-container--cabinet">
                <header class="lp-header lp-cabinet-header">
                    <div class="lp-cabinet-header__row">
                        <a href="{{ url('/') }}" class="lp-logo lp-cabinet-header__brand">{{ $brand }}</a>

                        <nav class="lp-cab-nav" aria-label="Разделы кабинета">
                            <a
                                href="{{ route('dashboard') }}"
                                class="lp-cab-nav__link {{ request()->routeIs('dashboard') ? 'lp-cab-nav__link--active' : '' }}"
                            >Мои подписки</a>
                            <a
                                href="{{ route('cabinet.profile') }}"
                                class="lp-cab-nav__link {{ request()->routeIs('cabinet.profile') ? 'lp-cab-nav__link--active' : '' }}"
                            >Профиль</a>
                            <a
                                href="{{ route('cabinet.settings') }}"
                                class="lp-cab-nav__link {{ request()->routeIs('cabinet.settings') ? 'lp-cab-nav__link--active' : '' }}"
                            >Устройства</a>
                            <a
                                href="{{ route('cabinet.payment') }}"
                                class="lp-cab-nav__link {{ request()->routeIs('cabinet.payment') ? 'lp-cab-nav__link--active' : '' }}"
                            >Тарифы и оплата</a>
                            <a
                                href="{{ route('cabinet.purchases') }}"
                                class="lp-cab-nav__link {{ request()->routeIs('cabinet.purchases') ? 'lp-cab-nav__link--active' : '' }}"
                            >История покупок</a>
                            <a
                                href="{{ $supportTgUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="lp-cab-nav__link"
                            >Поддержка</a>
                        </nav>

                        <div class="lp-cabinet-header__tools">
                            <button
                                type="button"
                                class="lp-header-burger md:hidden"
                                @click="mobileNav = true"
                                aria-expanded="false"
                                :aria-expanded="mobileNav"
                            >
                                <span class="sr-only">Открыть меню</span>
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <div class="hidden md:block">
                                <x-dropdown align="right" width="48" content-classes="py-1 bg-white lp-dropdown-panel">
                                    <x-slot name="trigger">
                                        <button type="button" class="lp-user-trigger">
                                            <span class="max-w-[10rem] truncate">{{ Auth::user()->name }}</span>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <div class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-600 border-b-2 border-black truncate">{{ Auth::user()->email }}</div>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                                Выйти
                                            </x-dropdown-link>
                                        </form>
                                    </x-slot>
                                </x-dropdown>
                            </div>
                        </div>
                    </div>
                </header>

                <div class="lp-cab-marquee" role="region" aria-label="Новости">
                    <a href="{{ $newsTgUrl }}" target="_blank" rel="noopener noreferrer" class="lp-cab-marquee__link" aria-label="Новости в Telegram">
                        <span class="lp-cab-marquee__viewport">
                            <span class="lp-cab-marquee__track" aria-hidden="true">
                                @php
                                    $tgIcon = '<svg class="lp-cab-marquee__tg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>';
                                    $d = '<span class="lp-cab-marquee__dot">•</span>';
                                    $item = fn($t) => $d.$tgIcon.'<span>'.$t.'</span>';
                                    $segment = $item('Поддержка').$item('Новости').$item('Актуальная информация').$item('Акции');
                                @endphp
                                <span class="lp-cab-marquee__segment">{!! $segment !!}{!! $segment !!}</span>
                                <span class="lp-cab-marquee__segment">{!! $segment !!}{!! $segment !!}</span>
                            </span>
                            <span class="lp-cab-marquee__sr">Поддержка · Новости · Актуальная информация · Акции</span>
                        </span>
                    </a>
                </div>

                <div
                    x-show="mobileNav"
                    x-transition.opacity
                    class="fixed inset-0 z-[60] md:hidden"
                    style="display: none;"
                    x-cloak
                >
                    <div class="absolute inset-0 bg-slate-900/50" @click="mobileNav = false" aria-hidden="true"></div>
                    <div
                        x-show="mobileNav"
                        x-transition:enter="transition transform ease-out duration-200"
                        x-transition:enter-start="-translate-x-full"
                        x-transition:enter-end="translate-x-0"
                        x-transition:leave="transition transform ease-in duration-150"
                        x-transition:leave-start="translate-x-0"
                        x-transition:leave-end="-translate-x-full"
                        class="absolute left-0 top-0 bottom-0 w-[min(100%,20rem)] bg-white lp-drawer flex flex-col"
                        @click.away="mobileNav = false"
                    >
                        <div class="flex items-center justify-between p-4 border-b-4 border-black">
                            <span class="font-black uppercase text-sm tracking-tight">{{ $brand }}</span>
                            <button type="button" class="lp-header-burger" @click="mobileNav = false" aria-label="Закрыть">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <nav class="flex-1 overflow-y-auto p-3 lp-drawer-nav" aria-label="Мобильное меню">
                            <a href="{{ route('dashboard') }}" @click="mobileNav = false" class="{{ request()->routeIs('dashboard') ? 'lp-cab-nav__link--active' : '' }}">Мои подписки</a>
                            <a href="{{ route('cabinet.profile') }}" @click="mobileNav = false" class="{{ request()->routeIs('cabinet.profile') ? 'lp-cab-nav__link--active' : '' }}">Профиль</a>
                            <a href="{{ route('cabinet.settings') }}" @click="mobileNav = false" class="{{ request()->routeIs('cabinet.settings') ? 'lp-cab-nav__link--active' : '' }}">Устройства</a>
                            <a href="{{ route('cabinet.payment') }}" @click="mobileNav = false" class="{{ request()->routeIs('cabinet.payment') ? 'lp-cab-nav__link--active' : '' }}">Тарифы и оплата</a>
                            <a href="{{ route('cabinet.purchases') }}" @click="mobileNav = false" class="{{ request()->routeIs('cabinet.purchases') ? 'lp-cab-nav__link--active' : '' }}">История покупок</a>
                            <a href="{{ $supportTgUrl }}" target="_blank" rel="noopener noreferrer" @click="mobileNav = false">Поддержка</a>
                        </nav>
                        <div class="p-4 border-t-4 border-black space-y-3">
                            <p class="text-xs font-bold uppercase text-slate-600 truncate">{{ Auth::user()->email }}</p>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full lp-login-btn" style="width:100%;text-align:center;">Выйти</button>
                            </form>
                        </div>
                    </div>
                </div>

                <main class="lp-cabinet-main">
                    {{ $slot }}
                </main>
            </div>
        </div>
        <style>[x-cloak]{display:none!important}</style>
    </body>
</html>

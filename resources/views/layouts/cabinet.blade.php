@props([])

@php
    $brand = config('marketing.brand_name', 'Надежда');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $brand }} — личный кабинет</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900">
        <div class="min-h-screen flex flex-col" x-data="{ mobileNav: false }">
            {{-- Верхняя шапка --}}
            <header class="sticky top-0 z-50 bg-white border-b border-slate-200 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex h-14 sm:h-16 items-center justify-between gap-4">
                        <div class="flex items-center gap-3 sm:gap-8 min-w-0 flex-1">
                            <a href="{{ url('/') }}" class="text-base sm:text-lg font-bold text-slate-900 tracking-tight shrink-0 hover:text-slate-700">
                                {{ $brand }}
                            </a>

                            {{-- Десктоп: пункты меню с подчёркиванием активного --}}
                            <nav class="hidden md:flex items-stretch gap-1 lg:gap-2" aria-label="Разделы кабинета">
                                <a
                                    href="{{ route('dashboard') }}"
                                    class="inline-flex items-center px-2 lg:px-3 py-2 text-sm font-semibold border-b-2 transition-colors {{ request()->routeIs('dashboard') ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}"
                                >Главная</a>
                                <a
                                    href="{{ route('cabinet.profile') }}"
                                    class="inline-flex items-center px-2 lg:px-3 py-2 text-sm font-semibold border-b-2 transition-colors {{ request()->routeIs('cabinet.profile') ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}"
                                >Профиль</a>
                                <a
                                    href="{{ route('cabinet.purchases') }}"
                                    class="inline-flex items-center px-2 lg:px-3 py-2 text-sm font-semibold border-b-2 transition-colors {{ request()->routeIs('cabinet.purchases') ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}"
                                >История покупок</a>
                            </nav>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            {{-- Мобильная кнопка меню --}}
                            <button
                                type="button"
                                class="md:hidden inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-400"
                                @click="mobileNav = true"
                                aria-expanded="false"
                                :aria-expanded="mobileNav"
                            >
                                <span class="sr-only">Открыть меню</span>
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            {{-- Профиль (десктоп) --}}
                            <div class="hidden sm:block">
                                <x-dropdown align="right" width="48">
                                    <x-slot name="trigger">
                                        <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400">
                                            <span class="max-w-[10rem] truncate">{{ Auth::user()->name }}</span>
                                            <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <div class="px-4 py-2 text-xs text-slate-500 border-b border-slate-100 truncate">{{ Auth::user()->email }}</div>
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
                </div>
            </header>

            {{-- Мобильный drawer --}}
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
                    class="absolute left-0 top-0 bottom-0 w-[min(100%,20rem)] bg-white shadow-xl flex flex-col"
                    @click.away="mobileNav = false"
                >
                    <div class="flex items-center justify-between p-4 border-b border-slate-200">
                        <span class="font-bold text-slate-900">{{ $brand }}</span>
                        <button type="button" class="p-2 rounded-lg text-slate-600 hover:bg-slate-100" @click="mobileNav = false" aria-label="Закрыть">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <nav class="flex-1 overflow-y-auto p-3 space-y-1" aria-label="Мобильное меню">
                        <a href="{{ route('dashboard') }}" @click="mobileNav = false" class="block rounded-xl px-4 py-3 text-sm font-semibold {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Главная</a>
                        <a href="{{ route('cabinet.profile') }}" @click="mobileNav = false" class="block rounded-xl px-4 py-3 text-sm font-semibold {{ request()->routeIs('cabinet.profile') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">Профиль</a>
                        <a href="{{ route('cabinet.purchases') }}" @click="mobileNav = false" class="block rounded-xl px-4 py-3 text-sm font-semibold {{ request()->routeIs('cabinet.purchases') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">История покупок</a>
                    </nav>
                    <div class="p-4 border-t border-slate-200 space-y-3">
                        <p class="text-xs text-slate-500 truncate">{{ Auth::user()->email }}</p>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full rounded-xl border border-slate-200 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">Выйти</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Контент (без второй «шапки»-полосы) --}}
            <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
                {{ $slot }}
            </main>
        </div>
        <style>[x-cloak]{display:none!important}</style>
    </body>
</html>

@extends('layouts.marketing')

@php
    $brand = config('marketing.brand_name', 'Надежда');
@endphp

@section('title', $brand.' — защищённый доступ в сеть')
@section('meta_description', 'Подписка «'.config('marketing.brand_name', 'Надежда').'»: основные или все локации, личный кабинет, без лимита объёма на нашей стороне. Тарифы для одного пользователя и семьи.')

@section('content')
    @php
        $tg = config('marketing.telegram_url', 'https://t.me/');
    @endphp
    <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur-md">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 flex items-center justify-between h-16 gap-4">
            <a href="{{ url('/') }}" class="text-lg font-bold tracking-tight text-slate-900 shrink-0">{{ $brand }}</a>
            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
                <a href="#about" class="hover:text-slate-900 transition-colors">О сервисе</a>
                <a href="#tarify" class="hover:text-slate-900 transition-colors">Тарифы</a>
                <a href="#contacts" class="hover:text-slate-900 transition-colors">Контакты</a>
            </nav>
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl bg-slate-900 text-white px-3 sm:px-4 py-2 text-sm font-bold hover:bg-slate-800 transition-colors">Личный кабинет</a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 px-2">Войти</a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-slate-900 text-white px-3 sm:px-4 py-2 text-sm font-bold hover:bg-slate-800 transition-colors">Регистрация</a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden bg-gradient-to-b from-slate-900 via-slate-900 to-slate-800 text-white">
            <div class="absolute inset-0 opacity-[0.07] bg-[radial-gradient(circle_at_30%_20%,#fff_0%,transparent_50%)]"></div>
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-20 sm:py-28 relative">
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight max-w-3xl">
                    {{ $brand }}: одна подписка — много точек доступа по всему миру
                </h1>
                <p class="mt-6 text-lg text-slate-300 max-w-2xl leading-relaxed">
                    Один ключ открывает сразу много регионов. Защищённое соединение, без ограничения объёма трафика на нашей стороне, простой импорт в привычные приложения.
                </p>
                <div class="mt-10 flex flex-wrap gap-4">
                    <a href="#tarify" class="inline-flex items-center justify-center rounded-xl bg-teal-500 text-slate-900 px-6 py-3.5 text-sm font-bold hover:bg-teal-400 transition-colors min-h-[48px]">
                        Выбрать тариф
                    </a>
                    <a href="#contacts" class="inline-flex items-center justify-center rounded-xl border border-white/25 bg-white/5 px-6 py-3.5 text-sm font-bold text-white hover:bg-white/10 transition-colors min-h-[48px]">
                        Написать в поддержку
                    </a>
                </div>
            </div>
        </section>

        <section id="about" class="py-16 sm:py-20 bg-white border-b border-slate-100">
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Почему мы</h2>
                <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <article class="rounded-2xl border border-slate-200 bg-slate-50/50 p-6 ring-1 ring-slate-900/5">
                        <h3 class="text-lg font-bold text-slate-900">Много локаций в одной подписке</h3>
                        <p class="mt-3 text-slate-600 text-sm leading-relaxed">Выбирайте подходящую точку под задачу: скорость, стабильность, доступ к нужным сервисам — без отдельных оплат за каждый регион.</p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-slate-50/50 p-6 ring-1 ring-slate-900/5">
                        <h3 class="text-lg font-bold text-slate-900">Удобно на всех устройствах</h3>
                        <p class="mt-3 text-slate-600 text-sm leading-relaxed">Подключайтесь с телефона, планшета или компьютера — настраивается за пару шагов в популярных клиентах.</p>
                    </article>
                    <article class="rounded-2xl border border-slate-200 bg-slate-50/50 p-6 ring-1 ring-slate-900/5 sm:col-span-2 lg:col-span-1">
                        <h3 class="text-lg font-bold text-slate-900">Личный кабинет</h3>
                        <p class="mt-3 text-slate-600 text-sm leading-relaxed">После регистрации видите свои подписки, общую ссылку для импорта и отдельные строки подключения.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="tarify" class="py-16 sm:py-24 bg-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight text-center">Тарифы</h2>
                <p class="mt-3 text-center text-slate-600 max-w-xl mx-auto text-sm sm:text-base">Период 30 дней. Оплата и продление — по договорённости с поддержкой (скоро — онлайн-оплата).</p>
                <div class="mt-12 grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                    <article class="rounded-3xl border-2 border-slate-200 bg-slate-50/80 p-8 flex flex-col ring-1 ring-slate-900/5">
                        <h3 class="text-xl font-bold text-slate-900">Личный</h3>
                        <p class="mt-2 text-slate-600 text-sm">До 2 устройств одновременно.</p>
                        <p class="mt-8 text-4xl font-extrabold text-slate-900 tabular-nums">250&nbsp;₽</p>
                        <p class="text-sm text-slate-500 mt-1">на 30 дней</p>
                        <ul class="mt-6 space-y-2 text-sm text-slate-700">
                            <li class="flex gap-2"><span class="text-teal-600 font-bold">✓</span> Доступ к <strong>основным</strong> локациям (набор постоянно развивается)</li>
                            <li class="flex gap-2"><span class="text-teal-600 font-bold">✓</span> Личный кабинет</li>
                        </ul>
                        <a href="#contacts" class="mt-8 inline-flex justify-center rounded-xl bg-slate-900 text-white py-3.5 text-sm font-bold hover:bg-slate-800 transition-colors">Связаться</a>
                    </article>
                    <article class="rounded-3xl border-2 border-teal-500/60 bg-gradient-to-b from-teal-50/80 to-white p-8 flex flex-col shadow-lg shadow-teal-900/10 ring-1 ring-teal-900/10 relative">
                        <span class="absolute top-4 right-4 text-[10px] font-bold uppercase tracking-wider bg-teal-600 text-white px-2 py-1 rounded-lg">Семья</span>
                        <h3 class="text-xl font-bold text-slate-900">Семейный</h3>
                        <p class="mt-2 text-slate-600 text-sm">До 5 устройств одновременно.</p>
                        <p class="mt-8 text-4xl font-extrabold text-slate-900 tabular-nums">590&nbsp;₽</p>
                        <p class="text-sm text-slate-500 mt-1">на 30 дней</p>
                        <ul class="mt-6 space-y-2 text-sm text-slate-700">
                            <li class="flex gap-2"><span class="text-teal-600 font-bold">✓</span> Доступ ко <strong>всем</strong> локациям сети (список расширяется)</li>
                            <li class="flex gap-2"><span class="text-teal-600 font-bold">✓</span> Больше устройств для дома</li>
                            <li class="flex gap-2"><span class="text-teal-600 font-bold">✓</span> Личный кабинет</li>
                        </ul>
                        <a href="#contacts" class="mt-8 inline-flex justify-center rounded-xl bg-teal-600 text-white py-3.5 text-sm font-bold hover:bg-teal-500 transition-colors">Оформить</a>
                    </article>
                </div>
            </div>
        </section>

        <section id="contacts" class="py-16 sm:py-20 bg-slate-900 text-white">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 text-center">
                <h2 class="text-2xl sm:text-3xl font-bold tracking-tight">Контакты</h2>
                <p class="mt-4 text-slate-300 max-w-lg mx-auto text-sm sm:text-base">Напишите в Telegram — подскажем по тарифу, оплате и подключению.</p>
                <a href="{{ $tg }}" target="_blank" rel="noopener noreferrer" class="mt-8 inline-flex items-center justify-center rounded-xl bg-teal-500 text-slate-900 px-8 py-3.5 text-sm font-bold hover:bg-teal-400 transition-colors min-h-[48px]">
                    Открыть Telegram
                </a>
                @if (filled(config('marketing.support_email')))
                    <p class="mt-6 text-sm text-slate-400">Или почта: <a href="mailto:{{ config('marketing.support_email') }}" class="text-teal-400 hover:underline">{{ config('marketing.support_email') }}</a></p>
                @endif
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-500">
            <p>© {{ date('Y') }} {{ $brand }}. Сервис для пользователей 18+.</p>
            <div class="flex gap-6">
                @guest
                    <a href="{{ route('login') }}" class="hover:text-slate-800">Вход</a>
                    <a href="{{ route('register') }}" class="hover:text-slate-800">Регистрация</a>
                @else
                    <a href="{{ route('dashboard') }}" class="hover:text-slate-800">Кабинет</a>
                @endguest
            </div>
        </div>
    </footer>
@endsection

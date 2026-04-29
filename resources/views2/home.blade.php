@extends('views2::layouts.marketing')

@php
    $brand = config('marketing.brand_name', 'Надежда');
    $tg = config('marketing.telegram_support_url', config('marketing.telegram_url', 'https://t.me/nadezhda_tehsup'));
@endphp

@section('title', $brand.' — стабильный интернет без зависаний')
@section('meta_description', 'Сервис для стабильной работы интернет-соединения и доступа к онлайн-сервисам. Быстро, просто и без лишних настроек.')

@push('styles')
    @include('views2::partials.lp-f1-styles')
    @include('views2::partials.lp-header-views2-styles')
    @include('views2::partials.lp-hero-marquee-mock-styles')
@endpush

@section('content')
<div class="lp-f1 lp-f1-body">
    <div class="lp-container">
        <header class="lp-header lp-header-v2">
            <div class="lp-brand-line">
                <span class="lp-logo-heavy">{{ mb_strtoupper($brand, 'UTF-8') }}</span>
                <span class="lp-logo-vpn">VPN</span>
            </div>
            <nav class="lp-header__nav" aria-label="Разделы страницы">
                <a href="#about">О сервисе</a>
                <a href="#tarify">Тарифы</a>
                <a href="#support">Поддержка</a>
            </nav>
            @auth
                <a href="{{ route('dashboard') }}" class="lp-header-cta">Кабинет</a>
            @else
                <a href="{{ route('register') }}" class="lp-header-cta">Попробовать</a>
            @endauth
        </header>

        <section class="hero">
            <div class="hero-content">
                <div class="trust-badge">Сделали как для своих</div>
                <h1 class="hero-title">
                    Интернет,<br>
                    который <span class="hero-title-em">работает</span>
                </h1>
                <p class="hero-description">
                    Стабильное соединение, быстрая загрузка и комфортная работа с привычными онлайн-сервисами. Без лишних настроек.
                </p>
                <div class="hero-buttons">
                    @guest
                        <a href="{{ route('register') }}" class="btn-cta btn-cta--primary">
                            Попробовать бесплатно (8ч)
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn-cta btn-cta--primary">
                            Личный кабинет
                        </a>
                    @endguest
                </div>
                @guest
                    <p class="hero-note">Без привязки карты. Никаких скрытых платежей.</p>
                @else
                    <p class="hero-note">Ваши подписки и ссылки для подключения.</p>
                @endguest
            </div>
            <div class="hero-img">
                <div class="sticker">
                    СТАБИЛЬНО<br>24/7
                </div>
            </div>
        </section>

        <div class="marquee" aria-hidden="true">
            <div class="marquee-track">
                <span class="marquee-segment">&nbsp;&nbsp;★ СТАБИЛЬНЫЙ ИНТЕРНЕТ ★ БЕЗ ЗАВИСАНИЙ ★ БЫСТРАЯ ЗАГРУЗКА ★ ПОДДЕРЖКА 24/7 ★</span>
                <span class="marquee-segment">&nbsp;&nbsp;★ СТАБИЛЬНЫЙ ИНТЕРНЕТ ★ БЕЗ ЗАВИСАНИЙ ★ БЫСТРАЯ ЗАГРУЗКА ★ ПОДДЕРЖКА 24/7 ★</span>
            </div>
        </div>

        <div id="about" class="lp-manifesto">
            <h2>Коротко о главном</h2>
            <p>Мы создали этот сервис, когда заметили, что интернет стал нестабильным: сообщения не доходят, страницы долго загружаются, связь прерывается.</p>
            <p><strong>Наша задача — вернуть стабильность и удобство повседневного использования сети.</strong></p>
        </div>

        <div class="lp-features">
            <div class="lp-feature-cell">
                <h3 class="lp-feature-title">Комфорт каждый день</h3>
                <p class="lp-feature-desc">Сервис работает в фоне и не требует постоянного переключения. Всё функционирует так, как вы привыкли.</p>
            </div>
            <div class="lp-feature-cell">
                <h3 class="lp-feature-title">Быстрая работа</h3>
                <p class="lp-feature-desc">Видео, сайты и приложения загружаются без задержек и подвисаний.</p>
            </div>
            <div class="lp-feature-cell">
                <h3 class="lp-feature-title">Современные сервисы</h3>
                <p class="lp-feature-desc">Поддержка стабильной работы популярных онлайн-платформ и инструментов, включая нейросети.</p>
            </div>
            <div class="lp-feature-cell">
                <h3 class="lp-feature-title">Надёжное соединение</h3>
                <p class="lp-feature-desc">Стабильная работа даже при нестабильном качестве сети или помехах.</p>
            </div>
        </div>

        <div id="tarify" class="lp-pricing">
            <h2 class="lp-section-title">Понятные цены</h2>
            <div class="lp-tariff-cards">
                @include('views2::partials.pricing-tariff-cards', ['showPayButtons' => false])
            </div>
            @guest
                <a href="{{ route('login') }}" class="lp-cta-btn">
                    <span>Подключиться</span>
                    <span aria-hidden="true">→</span>
                </a>
            @else
                <a href="{{ route('dashboard') }}" class="lp-cta-btn">
                    <span>Подключиться</span>
                    <span aria-hidden="true">→</span>
                </a>
            @endguest
            @include('views2::partials.pricing-payment-notes')
        </div>

        <div id="support" class="lp-support">
            <h2 class="lp-support-title">Поддержка</h2>
            <p class="lp-support-text">У нас нет роботов. Вам ответит живой человек, который поможет с настройкой и ответит на любые вопросы.</p>
            <div class="lp-support-time">Среднее время ответа — 7 минут</div>
            <a href="{{ $tg }}" target="_blank" rel="noopener noreferrer">Написать в Telegram</a>
        </div>

        <div class="lp-footer">
            {{ $brand }} — сервис стабильной передачи данных.<br><br>
            Пользователь самостоятельно определяет цели использования сервиса и несёт ответственность за соблюдение применимого законодательства.<br><br>
            @include('views2::partials.lp-footer-support')
            <a href="{{ route('agreement') }}" class="text-inherit underline underline-offset-2">Публичная оферта</a>
            · <a href="{{ route('privacy') }}" class="text-inherit underline underline-offset-2">Политика конфиденциальности</a>
            · <a href="{{ route('terms') }}" class="text-inherit underline underline-offset-2">Пользовательское соглашение</a>
        </div>
    </div>
</div>
@endsection

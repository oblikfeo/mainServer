@extends('layouts.marketing')

@php
    $brand = config('marketing.brand_name', 'Надежда');
    $tg = config('marketing.telegram_support_url', config('marketing.telegram_url', 'https://t.me/nadezhda_tehsup'));
@endphp

@section('title', $brand.' — стабильный интернет без зависаний')
@section('meta_description', 'Сервис для стабильной работы интернет-соединения и доступа к онлайн-сервисам. Быстро, просто и без лишних настроек.')

@push('styles')
    @include('partials.lp-f1-styles')
@endpush

@section('content')
<div class="lp-f1 lp-f1-body">
    <div class="lp-container">
        <div class="lp-header">
            <div class="lp-logo">{{ $brand }}</div>
            @auth
                <a href="{{ route('dashboard') }}" class="lp-login-btn">Кабинет</a>
            @else
                <a href="{{ route('login') }}" class="lp-login-btn">Кабинет</a>
            @endauth
        </div>

        <div class="lp-hero">
            <span class="lp-trust-tag">✓ Сделали как для своих</span>
            <h1>Интернет, который просто работает</h1>
            <p>Стабильное соединение, быстрая загрузка и комфортная работа с привычными онлайн-сервисами. Без лишних настроек.</p>
        </div>

        @guest
            <a href="{{ route('register') }}" class="lp-cta-btn">
                <span>Попробовать бесплатно (8ч)</span>
                <span aria-hidden="true">→</span>
            </a>
            <span class="lp-micro-copy">Без привязки карты. Никаких скрытых платежей.</span>
        @else
            <a href="{{ route('dashboard') }}" class="lp-cta-btn">
                <span>Личный кабинет</span>
                <span aria-hidden="true">→</span>
            </a>
            <span class="lp-micro-copy">Ваши подписки и ссылки для подключения.</span>
        @endguest

        <div class="lp-manifesto">
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
                <article class="lp-tariff-card lp-tariff-card--solo" aria-labelledby="tariff-solo-title">
                    <header class="lp-tariff-card__head">
                        <h3 class="lp-tariff-card__title" id="tariff-solo-title">Для себя</h3>
                        <p class="lp-tariff-card__meta">2 устройства</p>
                    </header>
                    <div class="lp-tariff-card__body">
                        <div class="lp-tariff-card__row">
                            <span class="lp-tariff-card__period">1 месяц</span>
                            <div class="lp-tariff-card__price-block">
                                <span class="lp-tariff-card__amount">250&nbsp;₽</span>
                            </div>
                        </div>
                        <div class="lp-tariff-card__row">
                            <span class="lp-tariff-card__period">3 месяца</span>
                            <div class="lp-tariff-card__price-block">
                                <span class="lp-tariff-card__amount">600&nbsp;₽</span>
                                <span class="lp-price-sub">Выгода 150&nbsp;₽</span>
                            </div>
                        </div>
                        <div class="lp-tariff-card__row">
                            <span class="lp-tariff-card__period">6 месяцев</span>
                            <div class="lp-tariff-card__price-block">
                                <span class="lp-tariff-card__amount">990&nbsp;₽</span>
                                <span class="lp-price-sub">165&nbsp;₽/мес<br>как чашка кофе</span>
                            </div>
                        </div>
                    </div>
                </article>
                <article class="lp-tariff-card lp-tariff-card--family" aria-labelledby="tariff-family-title">
                    <header class="lp-tariff-card__head">
                        <h3 class="lp-tariff-card__title" id="tariff-family-title">Для семьи</h3>
                        <p class="lp-tariff-card__meta">5 устройств</p>
                    </header>
                    <div class="lp-tariff-card__body">
                        <div class="lp-tariff-card__row">
                            <span class="lp-tariff-card__period">1 месяц</span>
                            <div class="lp-tariff-card__price-block">
                                <span class="lp-tariff-card__amount">550&nbsp;₽</span>
                            </div>
                        </div>
                        <div class="lp-tariff-card__row">
                            <span class="lp-tariff-card__period">3 месяца</span>
                            <div class="lp-tariff-card__price-block">
                                <span class="lp-tariff-card__amount">1350&nbsp;₽</span>
                                <span class="lp-price-sub">Выгода 300&nbsp;₽</span>
                            </div>
                        </div>
                        <div class="lp-tariff-card__row">
                            <span class="lp-tariff-card__period">6 месяцев</span>
                            <div class="lp-tariff-card__price-block">
                                <span class="lp-tariff-card__amount-line lp-tariff-card__amount-line--stack">
                                    <span class="lp-tariff-card__amount">2400&nbsp;₽</span>
                                    <span class="lp-badge">Выбор семей</span>
                                </span>
                                <span class="lp-price-sub">Максимальная выгода</span>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
            <button type="button" class="lp-cta-btn" id="lp-tariff-connect-btn">
                <span>Подключиться</span>
                <span aria-hidden="true">→</span>
            </button>
            <div class="lp-payment-info">
                <span>✓ Оплата через СБП или банковской картой РФ.</span>
                <span>✓ <strong>Без автопродлений:</strong> мы не списываем деньги втихую. Вы сами продлеваете доступ.</span>
                <span>✓ <strong>Гарантия:</strong> вернём оплату в течение 24 часов, если сервис не заработал.</span>
            </div>
        </div>

        <div class="lp-support">
            <h2 class="lp-support-title">Поддержка</h2>
            <p class="lp-support-text">У нас нет роботов. Вам ответит живой человек, который поможет с настройкой и ответит на любые вопросы.</p>
            <div class="lp-support-time">Среднее время ответа — 7 минут</div>
            <a href="{{ $tg }}" target="_blank" rel="noopener noreferrer">Написать в Telegram</a>
        </div>

        <div class="lp-footer">
            {{ $brand }} — сервис стабильной передачи данных.<br><br>
            Пользователь самостоятельно определяет цели использования сервиса и несёт ответственность за соблюдение применимого законодательства.<br><br>
            @include('partials.lp-footer-support')
            <a href="{{ route('agreement') }}" class="text-inherit underline underline-offset-2">Публичная оферта</a>
            · <a href="{{ route('privacy') }}" class="text-inherit underline underline-offset-2">Политика конфиденциальности</a>
        </div>
    </div>
</div>
@endsection

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
                <div class="lp-feature-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                </div>
                <h3 class="lp-feature-title">Комфорт каждый день</h3>
                <p class="lp-feature-desc">Сервис работает в фоне и не требует постоянного переключения. Всё функционирует так, как вы привыкли.</p>
            </div>
            <div class="lp-feature-cell">
                <div class="lp-feature-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
                <h3 class="lp-feature-title">Быстрая работа</h3>
                <p class="lp-feature-desc">Видео, сайты и приложения загружаются без задержек и подвисаний.</p>
            </div>
            <div class="lp-feature-cell">
                <div class="lp-feature-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75a2.25 2.25 0 012.25-2.25h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25v-2.25z" />
                    </svg>
                </div>
                <h3 class="lp-feature-title">Современные сервисы</h3>
                <p class="lp-feature-desc">Поддержка стабильной работы популярных онлайн-платформ и инструментов, включая нейросети.</p>
            </div>
            <div class="lp-feature-cell">
                <div class="lp-feature-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8.288 15.036a6.167 6.167 0 0110.423 0M17.017 11.39a9.18 9.18 0 00-10.034 0M12 19.5v.01m-7.06-7.551a12.113 12.113 0 0114.12 0" />
                    </svg>
                </div>
                <h3 class="lp-feature-title">Надёжное соединение</h3>
                <p class="lp-feature-desc">Стабильная работа даже при нестабильном качестве сети или помехах.</p>
            </div>
        </div>

        <div id="tarify" class="lp-pricing">
            <h2 class="lp-section-title">Понятные цены</h2>
            <div class="lp-price-board">
                <div class="lp-price-board__grid">
                    <article class="lp-price-pillar" aria-labelledby="tariff-solo">
                        <header class="lp-price-pillar__head">
                            <span class="lp-price-pillar__name" id="tariff-solo">Для себя</span>
                            <span class="lp-price-pillar__hint">2 устройства</span>
                        </header>
                        <ul class="lp-price-rows">
                            <li class="lp-price-row">
                                <div class="lp-price-row__label">1 месяц</div>
                                <div class="lp-price-row__sum">250&nbsp;₽</div>
                            </li>
                            <li class="lp-price-row">
                                <div class="lp-price-row__label">3 месяца</div>
                                <div class="lp-price-row__sum">600&nbsp;₽</div>
                                <span class="lp-price-row__note">Выгода 150&nbsp;₽</span>
                            </li>
                            <li class="lp-price-row">
                                <div class="lp-price-row__label">6 месяцев</div>
                                <div class="lp-price-row__sum">990&nbsp;₽</div>
                                <span class="lp-price-row__note">165&nbsp;₽/мес — как чашка кофе</span>
                            </li>
                        </ul>
                    </article>
                    <article class="lp-price-pillar lp-price-pillar--family" aria-labelledby="tariff-family">
                        <header class="lp-price-pillar__head">
                            <span class="lp-price-pillar__name" id="tariff-family">Для семьи</span>
                            <span class="lp-price-pillar__hint">5 устройств</span>
                        </header>
                        <ul class="lp-price-rows">
                            <li class="lp-price-row">
                                <div class="lp-price-row__label">1 месяц</div>
                                <div class="lp-price-row__sum">550&nbsp;₽</div>
                            </li>
                            <li class="lp-price-row">
                                <div class="lp-price-row__label">3 месяца</div>
                                <div class="lp-price-row__sum">1350&nbsp;₽</div>
                                <span class="lp-price-row__note">Выгода 300&nbsp;₽</span>
                            </li>
                            <li class="lp-price-row">
                                <div class="lp-price-row__label">6 месяцев</div>
                                <div class="lp-price-row__sum">
                                    2400&nbsp;₽
                                    <span class="lp-pillar-badge">Выбор семей</span>
                                </div>
                                <span class="lp-price-row__note">Максимальная выгода</span>
                            </li>
                        </ul>
                    </article>
                </div>
            </div>
            <div class="lp-payment-info">
                <div class="lp-pay-line">
                    @include('partials.lp-icon-check')
                    <span class="lp-pay-text">Оплата через СБП или банковской картой РФ.</span>
                </div>
                <div class="lp-pay-line">
                    @include('partials.lp-icon-check')
                    <span class="lp-pay-text"><strong>Без автопродлений:</strong> мы не списываем деньги втихую. Вы сами продлеваете доступ.</span>
                </div>
                <div class="lp-pay-line">
                    @include('partials.lp-icon-check')
                    <span class="lp-pay-text"><strong>Гарантия:</strong> вернём оплату в течение 24 часов, если сервис не заработал.</span>
                </div>
            </div>
        </div>

        <div class="lp-support">
            <h2 class="lp-support-title">Поддержка</h2>
            <p class="lp-support-text">У нас нет роботов. Вам ответит живой человек, который поможет с настройкой и ответит на любые вопросы.</p>
            <div class="lp-support-time">Среднее время ответа — 7 минут</div>
            <a href="{{ $tg }}" target="_blank" rel="noopener noreferrer">Написать в Telegram</a>
            @if (filled(config('marketing.support_email')))
                <p class="lp-support-text" style="margin-top: 1rem;">Почта: <a href="mailto:{{ config('marketing.support_email') }}" style="color: #2980b9; font-weight: 700;">{{ config('marketing.support_email') }}</a></p>
            @endif
        </div>

        <div class="lp-footer">
            {{ $brand }} — сервис стабильной передачи данных.<br><br>
            Пользователь самостоятельно определяет цели использования сервиса и несёт ответственность за соблюдение применимого законодательства.<br><br>
            <a href="{{ route('agreement') }}" class="text-inherit underline underline-offset-2">Публичная оферта</a>
        </div>
    </div>
</div>
@endsection

@extends('layouts.marketing')

@php
    $brand = config('marketing.brand_name', 'Надежда');
    $tg = config('marketing.telegram_support_url', config('marketing.telegram_url', 'https://t.me/nadezhda_tehsup'));
    $tgHandle = config('marketing.telegram_support_handle', 'nadezhda_tehsup');
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
            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="lp-price-table">
                    <tr>
                        <th>Период</th>
                        <th>Для себя (2 устройства)</th>
                        <th>Для семьи (5 устройств)</th>
                    </tr>
                    <tr>
                        <td>1 месяц</td>
                        <td>250&nbsp;₽</td>
                        <td>550&nbsp;₽</td>
                    </tr>
                    <tr>
                        <td>3 месяца</td>
                        <td>600&nbsp;₽<br><span class="lp-price-sub">Выгода 150&nbsp;₽</span></td>
                        <td>1350&nbsp;₽<br><span class="lp-price-sub">Выгода 300&nbsp;₽</span></td>
                    </tr>
                    <tr>
                        <td>6 месяцев</td>
                        <td>990&nbsp;₽<br><span class="lp-price-sub">165&nbsp;₽/мес — как чашка кофе</span></td>
                        <td>2400&nbsp;₽ <span class="lp-badge">Выбор семей</span><br><span class="lp-price-sub">Максимальная выгода</span></td>
                    </tr>
                </table>
            </div>
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
            <p class="lp-support-text" style="font-size:0.75rem;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.5rem;">
                Техподдержка: <a href="{{ $tg }}" target="_blank" rel="noopener noreferrer" style="color:#2980b9;">{{ '@'.$tgHandle }}</a>
            </p>
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

@extends('layouts.marketing')

@php
    $brand = config('marketing.brand_name', 'Надежда');
    $tg = config('marketing.telegram_url', 'https://t.me/');
@endphp

@section('title', $brand.' — стабильный интернет без зависаний')
@section('meta_description', 'Сервис для стабильной работы интернет-соединения и доступа к онлайн-сервисам. Быстро, просто и без лишних настроек.')

@push('styles')
<style>
    .lp-f1 { --lp-ink: #111; --lp-orange: #FF4500; --lp-bg: #f4f4f4; box-sizing: border-box; }
    .lp-f1 *, .lp-f1 *::before, .lp-f1 *::after { box-sizing: inherit; }
    .lp-f1-body {
        background-color: var(--lp-bg);
        background-image: radial-gradient(#d1d1d1 1px, transparent 1px);
        background-size: 20px 20px;
        margin: 0;
        min-height: 100vh;
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        color: var(--lp-ink);
        padding: 1rem;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }
    @media (min-width: 768px) {
        .lp-f1-body { padding: 2.5rem; align-items: center; }
    }
    .lp-f1 .lp-container {
        background: #fff;
        width: 100%;
        max-width: 650px;
        border: 4px solid var(--lp-ink);
        box-shadow: 12px 12px 0 var(--lp-ink);
        position: relative;
    }
    @media (min-width: 768px) {
        .lp-f1 .lp-container { box-shadow: 15px 15px 0 var(--lp-ink); max-width: 680px; }
    }
    .lp-f1 .lp-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        border-bottom: 4px solid var(--lp-ink);
        gap: 0.75rem;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-header { padding: 1.25rem 1.75rem; }
    }
    .lp-f1 .lp-logo { font-size: 1rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em; }
    @media (min-width: 480px) {
        .lp-f1 .lp-logo { font-size: 1.125rem; }
    }
    .lp-f1 .lp-login-btn {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        border: 2px solid var(--lp-ink);
        padding: 0.5rem 0.85rem;
        text-decoration: none;
        color: var(--lp-ink);
        transition: background 0.2s, color 0.2s;
        white-space: nowrap;
        text-align: center;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-login-btn { font-size: 0.875rem; padding: 0.5rem 1rem; }
    }
    .lp-f1 .lp-login-btn:hover { background: var(--lp-ink); color: #fff; }
    .lp-f1 .lp-hero { padding: 1.75rem 1.25rem; }
    @media (min-width: 480px) {
        .lp-f1 .lp-hero { padding: 2.5rem 1.75rem; }
    }
    .lp-f1 .lp-trust-tag {
        display: inline-block;
        background: var(--lp-ink);
        color: #fff;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 0.35rem 0.65rem;
        margin-bottom: 1rem;
        letter-spacing: 0.06em;
    }
    .lp-f1 .lp-hero h1 {
        font-size: 1.65rem;
        line-height: 1.12;
        margin: 0 0 1rem 0;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: -0.03em;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-hero h1 { font-size: 2.125rem; }
    }
    @media (min-width: 768px) {
        .lp-f1 .lp-hero h1 { font-size: 2.375rem; }
    }
    .lp-f1 .lp-hero > p {
        font-size: 0.9375rem;
        font-weight: 500;
        line-height: 1.5;
        margin: 0 0 0.5rem 0;
        color: #333;
    }
    .lp-f1 .lp-cta-btn {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--lp-orange);
        color: #fff;
        padding: 1.15rem 1.25rem;
        text-transform: uppercase;
        font-weight: 900;
        font-size: 0.9375rem;
        border: none;
        border-top: 4px solid var(--lp-ink);
        border-bottom: 4px solid var(--lp-ink);
        width: 100%;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.2s;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-cta-btn { padding: 1.5rem 1.75rem; font-size: 1.125rem; }
    }
    .lp-f1 .lp-cta-btn:hover { background: #E03E00; color: #fff; }
    .lp-f1 .lp-micro-copy {
        display: block;
        text-align: center;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 0.85rem 0.75rem;
        border-bottom: 4px solid var(--lp-ink);
        background: #fbfbfb;
        line-height: 1.35;
    }
    .lp-f1 .lp-manifesto {
        background: #fffde7;
        padding: 1.5rem 1.25rem;
        border-bottom: 4px solid var(--lp-ink);
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-manifesto { padding: 1.75rem 1.75rem; }
    }
    .lp-f1 .lp-manifesto h2 {
        font-size: 1.125rem;
        font-weight: 900;
        text-transform: uppercase;
        margin: 0 0 0.75rem 0;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-manifesto h2 { font-size: 1.375rem; }
    }
    .lp-f1 .lp-manifesto p {
        font-size: 0.875rem;
        line-height: 1.6;
        font-weight: 500;
        color: #222;
        margin: 0 0 0.85rem 0;
    }
    .lp-f1 .lp-manifesto p:last-child { margin-bottom: 0; }
    .lp-f1 .lp-features {
        display: grid;
        grid-template-columns: 1fr;
        border-bottom: 4px solid var(--lp-ink);
    }
    @media (min-width: 520px) {
        .lp-f1 .lp-features { grid-template-columns: 1fr 1fr; }
    }
    .lp-f1 .lp-feature-cell {
        padding: 1.25rem 1rem;
        border-bottom: 4px solid var(--lp-ink);
        border-right: none;
    }
    @media (min-width: 520px) {
        .lp-f1 .lp-feature-cell {
            padding: 1.5rem 1.25rem;
            border-right: 4px solid var(--lp-ink);
        }
        .lp-f1 .lp-feature-cell:nth-child(even) { border-right: none; }
        .lp-f1 .lp-feature-cell:nth-last-child(-n+2) { border-bottom: none; }
    }
    @media (max-width: 519px) {
        .lp-f1 .lp-feature-cell:last-child { border-bottom: none; }
    }
    .lp-f1 .lp-feature-title { font-size: 1rem; font-weight: 900; text-transform: uppercase; margin: 0 0 0.65rem 0; }
    @media (min-width: 480px) {
        .lp-f1 .lp-feature-title { font-size: 1.125rem; }
    }
    .lp-f1 .lp-feature-desc { font-size: 0.8125rem; font-weight: 500; color: #444; margin: 0; line-height: 1.4; }
    .lp-f1 .lp-pricing { border-top: 4px solid var(--lp-ink); }
    .lp-f1 .lp-section-title {
        font-size: 1.125rem;
        font-weight: 900;
        text-transform: uppercase;
        padding: 1.25rem 1.25rem;
        margin: 0;
        border-bottom: 4px solid var(--lp-ink);
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-section-title { font-size: 1.375rem; padding: 1.5rem 1.75rem; }
    }
    .lp-f1 .lp-price-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
    @media (min-width: 480px) {
        .lp-f1 .lp-price-table { font-size: 0.9375rem; }
    }
    .lp-f1 .lp-price-table th,
    .lp-f1 .lp-price-table td {
        border-bottom: 2px solid var(--lp-ink);
        padding: 0.85rem 0.65rem;
        text-align: left;
        vertical-align: top;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-price-table th,
        .lp-f1 .lp-price-table td { padding: 1.15rem 1rem; }
    }
    .lp-f1 .lp-price-table th {
        font-weight: 900;
        text-transform: uppercase;
        font-size: 0.5625rem;
        color: #666;
        line-height: 1.25;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-price-table th { font-size: 0.6875rem; }
    }
    .lp-f1 .lp-price-table td { font-weight: 600; }
    .lp-f1 .lp-price-sub {
        display: block;
        font-size: 0.625rem;
        color: var(--lp-orange);
        font-weight: 800;
        margin-top: 0.25rem;
        line-height: 1.3;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-price-sub { font-size: 0.6875rem; }
    }
    .lp-f1 .lp-badge {
        display: inline-block;
        background: var(--lp-orange);
        color: #fff;
        font-size: 0.5625rem;
        padding: 0.125rem 0.35rem;
        margin-left: 0.25rem;
        vertical-align: middle;
        text-transform: uppercase;
    }
    .lp-f1 .lp-payment-info {
        padding: 1.25rem 1.25rem;
        background: #fbfbfb;
        font-size: 0.75rem;
        font-weight: 600;
        color: #444;
        line-height: 1.55;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-payment-info { padding: 1.5rem 1.75rem; font-size: 0.8125rem; }
    }
    .lp-f1 .lp-payment-info span { display: block; margin-bottom: 0.35rem; color: var(--lp-ink); }
    .lp-f1 .lp-payment-info span:last-child { margin-bottom: 0; }
    .lp-f1 .lp-support {
        border-top: 4px solid var(--lp-ink);
        border-bottom: 4px solid var(--lp-ink);
        padding: 1.5rem 1.25rem;
        background: #e8f4f8;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-support { padding: 1.75rem 1.75rem; }
    }
    .lp-f1 .lp-support-title { font-size: 1.125rem; font-weight: 900; text-transform: uppercase; margin: 0 0 0.75rem 0; }
    @media (min-width: 480px) {
        .lp-f1 .lp-support-title { font-size: 1.25rem; }
    }
    .lp-f1 .lp-support-text { font-size: 0.875rem; margin: 0 0 0.5rem 0; font-weight: 500; color: #222; }
    .lp-f1 .lp-support-time { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #2980b9; }
    .lp-f1 .lp-support a {
        display: inline-block;
        margin-top: 1rem;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.75rem;
        color: var(--lp-ink);
        border: 2px solid var(--lp-ink);
        padding: 0.6rem 1rem;
        text-decoration: none;
    }
    .lp-f1 .lp-support a:hover { background: var(--lp-ink); color: #fff; }
    .lp-f1 .lp-footer {
        padding: 1.25rem 1rem;
        font-size: 0.5625rem;
        font-weight: 700;
        text-transform: uppercase;
        text-align: center;
        color: #555;
        line-height: 1.5;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-footer { padding: 1.5rem 1.75rem; font-size: 0.6875rem; }
    }
</style>
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

        <div class="lp-pricing">
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
            <a href="{{ $tg }}" target="_blank" rel="noopener noreferrer">Написать в Telegram</a>
            @if (filled(config('marketing.support_email')))
                <p class="lp-support-text" style="margin-top: 1rem;">Почта: <a href="mailto:{{ config('marketing.support_email') }}" style="color: #2980b9; font-weight: 700;">{{ config('marketing.support_email') }}</a></p>
            @endif
        </div>

        <div class="lp-footer">
            {{ $brand }} — сервис стабильной передачи данных.<br><br>
            Пользователь самостоятельно определяет цели использования сервиса и несёт ответственность за соблюдение применимого законодательства.
        </div>
    </div>
</div>
@endsection

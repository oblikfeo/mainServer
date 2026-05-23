@extends('views2::layouts.marketing')

@php
    $brand = config('marketing.brand_name', 'Надежда');
    $tg = config('marketing.telegram_support_url', config('marketing.telegram_url', 'https://t.me/nadezhda_tehsup'));
@endphp

@section('title', $brand.' — быстрая оплата')
@section('meta_description', 'Выберите тариф и оплатите подписку за пару кликов — без регистрации. Оплата через СБП прямо на сайте.')

@push('styles')
    @include('views2::partials.lp-f1-styles')
    @include('views2::partials.lp-header-views2-styles')
    @include('views2::partials.lp-pricing-support-footer-mock-styles')
    @include('views2::partials.lp-views2-responsive-styles')
    @include('cabinet.nice.partials.nice-styles')
    @include('quick-buy.partials.buy-styles')
@endpush

@section('content')
<div class="lp-f1 lp-f1-body">
    <div class="lp-container">
        <header class="lp-header lp-header-v2 lp-header--drawer">
            <div class="lp-header__bar">
                <a href="{{ url('/') }}" class="lp-brand-line" style="text-decoration:none;">
                    <span class="lp-logo-heavy">{{ mb_strtoupper($brand, 'UTF-8') }}</span>
                    <span class="lp-logo-vpn">VPN</span>
                </a>
                <nav class="lp-header__nav" id="lp-main-nav" aria-label="Быстрые ссылки">
                    <a href="{{ url('/') }}">Главная</a>
                    <a href="#tarify">Тарифы</a>
                    <a href="{{ $tg }}" target="_blank" rel="noopener noreferrer">Поддержка</a>
                </nav>
                <button
                    type="button"
                    class="lp-nav-toggle"
                    id="lp-nav-toggle"
                    aria-expanded="false"
                    aria-controls="lp-main-nav"
                    aria-label="Открыть меню"
                >
                    <span class="lp-nav-toggle__bars" aria-hidden="true"></span>
                </button>
                @auth
                    <a href="{{ route('dashboard') }}" class="lp-header-cta">Кабинет</a>
                @else
                    <a href="{{ route('login') }}" class="lp-header-cta">Войти</a>
                @endauth
            </div>
        </header>

        <section class="lp-nice-hero">
            <span class="lp-nice-hero__kicker">
                <span class="lp-nice-hero__kicker-dot" aria-hidden="true"></span>
                Без регистрации
            </span>
            <h1 class="lp-nice-hero__title">
                Оплата в <span class="lp-nice-hero__title-em">2 клика</span>
            </h1>
            <p class="lp-nice-hero__lead">
                Выберите тариф и оплатите через СБП — QR-код появится прямо здесь. Аккаунт создадим автоматически, подписку можно сразу добавить в Happ.
            </p>
        </section>

        <section id="tarify" class="lp-pricing lp-pricing-mock">
            <div class="lp-pricing-head-mock">
                <h2 class="lp-section-title">Выберите тариф</h2>
            </div>
            <div class="pricing-container">
                @include('quick-buy.partials.pricing-buy-plaques')
            </div>
            <div class="lp-pricing-guarantees">
                @include('views2::partials.pricing-payment-notes')
            </div>
        </section>

        <footer class="lp-footer-mock">
            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} {{ mb_strtoupper($brand, 'UTF-8') }}</span>
                <span>
                    Оплачивая подписку, вы соглашаетесь с
                    <a href="{{ route('agreement') }}" class="text-inherit underline underline-offset-2">публичной офертой</a>.
                </span>
            </div>
        </footer>
    </div>
</div>

<div class="lp-buy-modal" id="lp-buy-email-modal" role="dialog" aria-modal="true" aria-labelledby="lp-buy-email-title" aria-hidden="true">
    <div class="lp-buy-modal__panel">
        <button type="button" class="lp-buy-modal__close" id="lp-buy-email-close" aria-label="Закрыть">&times;</button>
        <h2 class="lp-buy-modal__title" id="lp-buy-email-title">Почта для подписки</h2>
        <p class="lp-buy-modal__sub" id="lp-buy-email-desc">Укажите email — отправим ссылку подписки после оплаты.</p>
        <p class="lp-buy-modal__amount" id="lp-buy-email-amount"></p>
        <form id="lp-buy-email-form" class="lp-buy-email-modal-form">
            <input type="email" id="lp-buy-email-input" name="email" required autocomplete="email" placeholder="your@email.com">
            <p class="lp-buy-modal__status lp-buy-modal__status--error" id="lp-buy-email-error" hidden></p>
            <button type="submit" class="lp-buy-pay-btn" id="lp-buy-email-submit">Перейти к оплате</button>
        </form>
        <p class="lp-buy-modal__hint">Нажимая «Перейти к оплате», вы соглашаетесь с <a href="{{ route('agreement') }}" class="text-inherit underline underline-offset-2">публичной офертой</a>.</p>
    </div>
</div>

<div class="lp-buy-modal" id="lp-buy-modal" role="dialog" aria-modal="true" aria-labelledby="lp-buy-modal-title" aria-hidden="true">
    <div class="lp-buy-modal__panel">
        <button type="button" class="lp-buy-modal__close" id="lp-buy-modal-close" aria-label="Закрыть">&times;</button>
        <h2 class="lp-buy-modal__title" id="lp-buy-modal-title">Оплата через СБП</h2>
        <p class="lp-buy-modal__sub" id="lp-buy-modal-desc">Отсканируйте QR-код в приложении банка</p>
        <p class="lp-buy-modal__amount" id="lp-buy-modal-amount"></p>
        <div class="lp-buy-modal__qr-wrap" id="lp-buy-modal-qr" aria-hidden="true"></div>
        <p class="lp-buy-modal__status" id="lp-buy-modal-status">Ожидаем оплату…</p>
        <p class="lp-buy-modal__hint">После оплаты страница обновится автоматически. Обычно это занимает до минуты.</p>
    </div>
</div>
@endsection

@push('scripts')
@include('quick-buy.partials.buy-script')
<script>
(function () {
    var toggle = document.getElementById('lp-nav-toggle');
    var nav = document.getElementById('lp-main-nav');
    if (!toggle || !nav) return;
    function setOpen(open) {
        nav.classList.toggle('lp-header__nav--open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
    }
    toggle.addEventListener('click', function () {
        setOpen(!nav.classList.contains('lp-header__nav--open'));
    });
    nav.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () { setOpen(false); });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setOpen(false);
    });
})();
</script>
@endpush

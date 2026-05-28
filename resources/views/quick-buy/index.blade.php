@extends('views2::layouts.marketing')

@php
    $brand = config('marketing.brand_name', 'Надежда');
    $tg = config('marketing.telegram_support_url', config('marketing.telegram_url', 'https://t.me/nadezhda_tehsup'));
@endphp

@section('title', $brand.' — быстрая оплата')
@section('meta_description', 'Выберите тариф и оплатите подписку за пару кликов — без регистрации. Оплата картой или СБП на защищённой странице платёжной системы.')

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
                Выберите тариф, укажите почту и перейдите к оплате. Аккаунт создадим автоматически — подписку можно сразу добавить в Happ.
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
        <span class="lp-buy-modal__kicker">
            <span class="lp-buy-modal__kicker-dot" aria-hidden="true"></span>
            Перед оплатой
        </span>
        <h2 class="lp-buy-modal__title" id="lp-buy-email-title">
            Укажите <span class="lp-buy-modal__title-em">почту</span>
        </h2>
        <p class="lp-buy-modal__sub" id="lp-buy-email-desc">Отправим ссылку подписки после оплаты — можно сразу добавить в Happ.</p>
        <div class="lp-buy-modal__amount-wrap">
            <span class="lp-buy-modal__amount-label">К оплате</span>
            <p class="lp-buy-modal__amount" id="lp-buy-email-amount"></p>
        </div>
        <form id="lp-buy-email-form" class="lp-buy-email-modal-form">
            <label class="lp-buy-modal__field-label" for="lp-buy-email-input">Email</label>
            <input type="email" id="lp-buy-email-input" name="email" required autocomplete="email" placeholder="your@email.com">
            <p class="lp-buy-modal__status lp-buy-modal__status--error" id="lp-buy-email-error" hidden></p>
            <button type="submit" class="lp-buy-pay-btn" id="lp-buy-email-submit">Перейти к оплате</button>
        </form>
        <p class="lp-buy-modal__hint">После оплаты вы вернётесь на наш сайт — там будет ссылка подписки. Дубликат отправим на указанный email.</p>
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

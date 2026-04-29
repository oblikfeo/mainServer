<style>
    /* Views2 /test: стабильная мобильная вёрстка — без горизонтального «плавания» */
    .lp-f1.lp-f1-body {
        overflow-x: clip;
        -webkit-overflow-scrolling: touch;
    }

    .lp-f1 .lp-container {
        min-width: 0;
    }

    @media (max-width: 767.98px) {
        .lp-f1 .lp-container {
            max-width: 100%;
            overflow-x: clip;
        }
    }

    @media (max-width: 479.98px) {
        .lp-f1.lp-f1-body {
            padding-left: max(0.5rem, env(safe-area-inset-left));
            padding-right: max(0.5rem, env(safe-area-inset-right));
        }
    }

    @media (max-width: 767.98px) {
        .lp-f1 #features,
        .lp-f1 #support,
        .lp-f1 #tarify {
            scroll-margin-top: min(130px, 28vh);
        }

        /* Шапка: колонка, без выталкивания за край */
        .lp-f1 .lp-header.lp-header-v2 {
            flex-direction: column;
            align-items: stretch;
            gap: 0.65rem;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .lp-f1 .lp-brand-line {
            justify-content: center;
            min-width: 0;
        }

        .lp-f1 .lp-logo-heavy {
            font-size: clamp(1.125rem, 5.5vw, 1.5rem);
        }

        .lp-f1 .lp-header__nav {
            flex: none;
            width: 100%;
            min-width: 0;
            justify-content: center;
            row-gap: 0.5rem;
        }

        .lp-f1 .lp-header-cta {
            margin-left: 0;
            align-self: center;
            width: 100%;
            max-width: 22rem;
        }

        /* Hero: без лишней высоты, текст не вылезает */
        .lp-f1 section.hero {
            min-height: 0;
        }

        .lp-f1 .hero-content {
            padding: 1.25rem 0.875rem;
            min-width: 0;
        }

        .lp-f1 .hero-title {
            font-size: clamp(1.65rem, 10vw, 2.85rem);
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .lp-f1 .hero-description {
            overflow-wrap: anywhere;
        }

        .lp-f1 .hero-img {
            min-height: 11rem;
            overflow: hidden;
        }

        .lp-f1 .sticker {
            right: max(0.5rem, env(safe-area-inset-right));
            bottom: 0.75rem;
            transform: rotate(12deg);
        }

        /* Бегущая строка: меньше трекинг на узком экране */
        .lp-f1 .marquee-segment {
            font-size: 0.8125rem;
            letter-spacing: 0.08em;
            padding-right: 1rem;
        }

        /* Features */
        .lp-f1 .lp-features-section.section-padding {
            padding-left: 0.875rem;
            padding-right: 0.875rem;
        }

        .lp-f1 .lp-features-section .section-title {
            font-size: clamp(1.65rem, 9vw, 2.5rem);
            margin-bottom: 1.125rem;
        }

        .lp-f1 .lp-features-section .about-text {
            margin-bottom: 2rem;
            overflow-wrap: anywhere;
        }

        /* Тарифы */
        .lp-f1 .lp-pricing.lp-pricing-mock {
            padding-left: 0.875rem;
            padding-right: 0.875rem;
            padding-top: 1.75rem;
            padding-bottom: 1.75rem;
        }

        .lp-f1 .lp-pricing.lp-pricing-mock .lp-pricing-head-mock {
            margin-bottom: 1.5rem;
        }

        .lp-f1 .lp-pricing.lp-pricing-mock .lp-section-title {
            font-size: clamp(1.45rem, 7.5vw, 2.25rem);
            overflow-wrap: anywhere;
        }

        .lp-f1 .lp-pricing.lp-pricing-mock .pricing-column-title {
            font-size: clamp(1rem, 4.5vw, 1.35rem);
        }

        .lp-f1 .lp-pricing.lp-pricing-mock .pricing-column-hint {
            font-size: 0.875rem;
        }

        .lp-f1 .lp-pricing.lp-pricing-mock .pricing-card {
            padding: 1rem 0.75rem;
            min-width: 0;
        }

        .lp-f1 .lp-pricing.lp-pricing-mock .pricing-price {
            font-size: clamp(1.65rem, 8vw, 2.25rem);
            overflow-wrap: anywhere;
        }

        .lp-f1 .lp-pricing.lp-pricing-mock .pricing-tag {
            max-width: calc(100% - 1rem);
            white-space: normal;
            text-align: center;
            line-height: 1.2;
        }

        .lp-f1 .lp-pricing.lp-pricing-mock .lp-pricing-guarantees .lp-payment-info {
            font-size: 0.8125rem;
            line-height: 1.65;
        }

        /* Поддержка */
        .lp-f1 .lp-support-section-mock.section-padding {
            padding-left: 0.875rem;
            padding-right: 0.875rem;
            padding-top: 2.5rem;
            padding-bottom: 2.5rem;
        }

        .lp-f1 .lp-support-section-mock .section-title {
            font-size: clamp(1.45rem, 7.5vw, 2.25rem);
        }

        .lp-f1 .lp-support-section-mock .support-text {
            margin-top: 1rem;
            margin-bottom: 1rem;
            overflow-wrap: anywhere;
        }

        .lp-f1 .lp-support-section-mock .support-time {
            font-size: clamp(1.45rem, 8vw, 2rem);
        }

        .lp-f1 .lp-support-section-mock .support-badge {
            padding: 1rem 1.25rem;
            max-width: 100%;
        }

        .lp-f1 .lp-support-section-mock .btn-cta.lp-support-tg-btn {
            width: 100%;
            max-width: 22rem;
            justify-content: center;
        }

        /* Футер */
        .lp-f1 .lp-footer-mock {
            padding-left: 0.875rem;
            padding-right: 0.875rem;
            gap: 1.75rem;
        }

        .lp-f1 .lp-footer-mock .footer-logo {
            font-size: clamp(1.35rem, 7vw, 2rem);
            overflow-wrap: anywhere;
        }

        .lp-f1 .lp-footer-mock .footer-description {
            overflow-wrap: anywhere;
        }
    }

    /* Hover-тени только там, где есть настоящий hover (меньше артефактов на таче) */
    @media (max-width: 767.98px) {
        .lp-f1 .lp-features-section .feature-card:hover,
        .lp-f1 .lp-pricing.lp-pricing-mock .pricing-card:hover {
            transform: none;
            box-shadow: none;
        }
    }

    @media (hover: hover) and (max-width: 767.98px) {
        .lp-f1 .lp-features-section .feature-card:hover {
            box-shadow: var(--mock-shadow);
            transform: translate(-4px, -4px);
        }

        .lp-f1 .lp-pricing.lp-pricing-mock .pricing-card:hover {
            box-shadow: var(--mock-shadow);
            transform: translate(-3px, -3px);
        }
    }
</style>

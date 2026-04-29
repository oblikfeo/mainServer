<style>
    /* Тарифы: оранжевый блок как в макете (цены из конфига — без изменений) */
    .lp-f1 .lp-pricing.lp-pricing-mock {
        margin: 0;
        padding: 40px 20px;
        background: var(--mock-primary);
        border-top: var(--mock-border);
        border-bottom: var(--mock-border);
        border-left: none;
        border-right: none;
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-pricing.lp-pricing-mock {
            padding: 60px 40px;
        }
    }

    @media (min-width: 1024px) {
        .lp-f1 .lp-pricing.lp-pricing-mock {
            padding: 80px 50px;
        }
    }

    .lp-f1 .lp-pricing.lp-pricing-mock .lp-pricing-head-mock {
        margin-bottom: 40px;
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-pricing-head-mock {
            margin-bottom: 60px;
        }
    }

    .lp-f1 .lp-pricing.lp-pricing-mock .lp-section-title {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 36px;
        font-weight: 800;
        text-transform: uppercase;
        line-height: 1;
        margin: 0;
        padding: 0;
        border: none;
        color: #fff;
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-section-title {
            font-size: 48px;
        }
    }

    @media (min-width: 1024px) {
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-section-title {
            font-size: 64px;
        }
    }

    .lp-f1 .lp-pricing.lp-pricing-mock .lp-tariff-cards {
        margin-bottom: 40px;
        border-bottom: none;
        background: transparent;
        padding-left: 0;
        padding-right: 0;
    }

    .lp-f1 .lp-pricing.lp-pricing-mock .lp-tariff-card {
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .lp-f1 .lp-pricing.lp-pricing-mock .lp-tariff-card:hover {
        box-shadow: var(--mock-shadow);
        transform: translate(-3px, -3px);
    }

    @media (prefers-reduced-motion: reduce) {
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-tariff-card:hover {
            transform: none;
        }
    }

    .lp-f1 .lp-pricing.lp-pricing-mock .lp-pricing-cta-wrap {
        text-align: center;
        margin-bottom: 30px;
    }

    .lp-f1 .lp-pricing.lp-pricing-mock .lp-pricing-guarantees .lp-payment-info {
        padding: 0;
        background: transparent;
        text-align: center;
        font-size: 14px;
        line-height: 2;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.92);
    }

    .lp-f1 .lp-pricing.lp-pricing-mock .lp-pricing-guarantees .lp-payment-info span {
        display: block;
        margin-bottom: 5px;
        color: inherit;
    }

    /* Поддержка — как макет */
    .lp-f1 .lp-support-section-mock.section-padding {
        padding: 60px 20px;
        border-bottom: var(--mock-border);
        background: transparent;
        text-align: center;
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-support-section-mock.section-padding {
            padding: 100px 40px;
        }
    }

    .lp-f1 .lp-support-section-mock .support-content {
        max-width: 600px;
        margin: 0 auto;
    }

    .lp-f1 .lp-support-section-mock .section-title {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 36px;
        font-weight: 800;
        text-transform: uppercase;
        line-height: 1;
        margin: 0 0 30px;
        color: var(--mock-dark);
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-support-section-mock .section-title {
            font-size: 48px;
        }
    }

    @media (min-width: 1024px) {
        .lp-f1 .lp-support-section-mock .section-title {
            font-size: 64px;
        }
    }

    .lp-f1 .lp-support-section-mock .support-text {
        font-size: 16px;
        line-height: 1.8;
        color: #555;
        margin: 30px 0;
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-support-section-mock .support-text {
            font-size: 18px;
        }
    }

    .lp-f1 .lp-support-section-mock .support-badge {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        background: #fff;
        border: var(--mock-border);
        padding: 20px 40px;
        margin-bottom: 30px;
    }

    .lp-f1 .lp-support-section-mock .support-time {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 36px;
        font-weight: 800;
        color: var(--mock-primary);
    }

    .lp-f1 .lp-support-section-mock .support-label {
        font-size: 14px;
        color: #666;
        text-transform: uppercase;
        font-weight: 700;
    }

    .lp-f1 .lp-support-section-mock .btn-cta.lp-support-tg-btn {
        background: var(--mock-dark);
        color: #fff;
        border: 3px solid #fff;
        box-shadow: 4px 4px 0 var(--mock-dark);
    }

    .lp-f1 .lp-support-section-mock .btn-cta.lp-support-tg-btn:hover {
        color: #fff;
        box-shadow: 6px 6px 0 var(--mock-dark);
    }

    /* Подвал — сетка как макет */
    .lp-f1 .lp-footer-mock {
        padding: 40px 20px;
        background: #fff;
        display: grid;
        grid-template-columns: 1fr;
        gap: 40px;
        border-bottom: var(--mock-border);
        text-align: left;
        font-size: 14px;
        font-weight: 400;
        text-transform: none;
        color: #444;
        line-height: 1.6;
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-footer-mock {
            padding: 60px 40px;
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (min-width: 1024px) {
        .lp-f1 .lp-footer-mock {
            padding: 80px 50px;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 50px;
        }
    }

    .lp-f1 .lp-footer-mock .footer-logo {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 20px;
        color: var(--mock-dark);
        text-transform: uppercase;
        line-height: 1;
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-footer-mock .footer-logo {
            font-size: 40px;
        }
    }

    .lp-f1 .lp-footer-mock .footer-description {
        color: #666;
        line-height: 1.6;
        margin: 0;
    }

    .lp-f1 .lp-footer-mock .footer-links h4 {
        text-transform: uppercase;
        margin-bottom: 20px;
        font-size: 18px;
        font-weight: 800;
        color: var(--mock-secondary);
    }

    .lp-f1 .lp-footer-mock .footer-links ul {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .lp-f1 .lp-footer-mock .footer-links li {
        margin-bottom: 10px;
    }

    .lp-f1 .lp-footer-mock .footer-links a {
        color: inherit;
        text-decoration: none;
        transition: color 0.2s;
    }

    .lp-f1 .lp-footer-mock .footer-links a:hover {
        color: var(--mock-primary);
    }

    .lp-f1 .lp-footer-mock .lp-footer-support {
        margin-top: 1rem;
        margin-bottom: 0;
        font-size: 14px;
        font-weight: 500;
        text-transform: none;
        letter-spacing: normal;
    }

    .lp-f1 .lp-footer-mock .lp-footer-support > div {
        margin: 0.35rem 0;
    }

    .lp-f1 .lp-footer-mock .footer-bottom {
        grid-column: 1;
        border-top: var(--mock-border);
        padding-top: 30px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        font-weight: 700;
        font-size: 12px;
        color: var(--mock-dark);
        text-transform: uppercase;
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-footer-mock .footer-bottom {
            grid-column: 1 / span 2;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            padding-top: 40px;
            font-size: 14px;
            gap: 0;
        }
    }

    @media (min-width: 1024px) {
        .lp-f1 .lp-footer-mock .footer-bottom {
            grid-column: 1 / span 3;
        }
    }

    .lp-f1 .lp-footer-mock .footer-docs a {
        color: inherit;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .lp-f1 .lp-footer-mock .footer-docs a:hover {
        color: var(--mock-primary);
    }
</style>

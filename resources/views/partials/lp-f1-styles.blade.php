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
    .lp-f1 button.lp-cta-btn { font-family: inherit; }
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
    .lp-f1 .lp-tariff-cards {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.15rem;
        padding: 1.15rem 1rem 1.35rem;
        border-bottom: 4px solid var(--lp-ink);
        background: #fff;
    }
    @media (min-width: 560px) {
        .lp-f1 .lp-tariff-cards {
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            padding: 1.35rem 1.25rem 1.5rem;
        }
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-tariff-cards { padding-left: 1.5rem; padding-right: 1.5rem; }
    }
    .lp-f1 .lp-tariff-card {
        display: flex;
        flex-direction: column;
        min-width: 0;
        background: #fff;
        border: 4px solid var(--lp-ink);
        box-shadow: 8px 8px 0 var(--lp-ink);
    }
    .lp-f1 .lp-tariff-card--solo {
        border-top: 6px solid var(--lp-orange);
    }
    .lp-f1 .lp-tariff-card--family {
        box-shadow: 8px 8px 0 var(--lp-orange);
    }
    .lp-f1 .lp-tariff-card__head {
        padding: 1rem 1.1rem;
        border-bottom: 4px solid var(--lp-ink);
        background: #f5f5f5;
    }
    .lp-f1 .lp-tariff-card--family .lp-tariff-card__head {
        background: white;
        color: var(--lp-ink);
        border-bottom: 4px solid var(--lp-orange);
    }
    .lp-f1 .lp-tariff-card__title {
        margin: 0;
        font-size: 1rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: -0.02em;
        line-height: 1.15;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-tariff-card__title { font-size: 1.0625rem; }
    }
    .lp-f1 .lp-tariff-card__meta {
        margin: 0.4rem 0 0 0;
        font-size: 0.625rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #555;
    }
    .lp-f1 .lp-tariff-card--family .lp-tariff-card__meta {
        color: black;
    }
    .lp-f1 .lp-tariff-card__body { flex: 1; }
    .lp-f1 .lp-tariff-card__row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.5rem 1rem;
        align-items: center;
        padding: 0.95rem 1.1rem;
        border-bottom: 2px solid var(--lp-ink);
    }
    .lp-f1 .lp-tariff-card__row:last-child { border-bottom: none; }
    .lp-f1 .lp-tariff-card__period {
        font-size: 0.6875rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #444;
        padding-top: 0.2rem;
    }
    .lp-f1 .lp-tariff-card__price-block {
        text-align: right;
        min-width: 0;
    }
    .lp-f1 .lp-tariff-card__amount {
        display: block;
        font-size: 1.35rem;
        font-weight: 900;
        font-variant-numeric: tabular-nums;
        line-height: 1.1;
        color: var(--lp-ink);
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-tariff-card__amount { font-size: 1.5rem; }
    }
    .lp-f1 .lp-tariff-card__price-block .lp-price-sub {
        margin-top: 0.35rem;
    }
    .lp-f1 .lp-tariff-card__amount-line {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: flex-end;
        gap: 0.35rem 0.5rem;
    }
    .lp-f1 .lp-tariff-card__amount-line .lp-tariff-card__amount {
        display: inline;
    }
    .lp-f1 .lp-tariff-card__amount-line--stack {
        flex-direction: column;
        align-items: flex-end;
        gap: 0.4rem;
    }
    .lp-f1 .lp-tariff-card__amount-line--stack .lp-tariff-card__amount {
        display: block;
    }
    .lp-f1 .lp-tariff-card__amount-line--stack .lp-badge {
        margin-left: 0;
    }
    .lp-f1 .lp-tariff-card--family .lp-badge {
        background: var(--lp-orange);
        color: #fff;
        border: 2px solid var(--lp-ink);
    }
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
    .lp-f1 .lp-footer-support {
        margin: 0.75rem 0 1rem;
        text-transform: none;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .lp-f1 .lp-footer-support > div { margin: 0.35rem 0; }

    .lp-f1-body.lp-f1-cabinet {
        align-items: flex-start;
    }
    @media (min-width: 768px) {
        .lp-f1-body.lp-f1-cabinet {
            align-items: flex-start;
        }
    }
    .lp-f1 .lp-container--narrow { max-width: 420px; }
    .lp-f1 .lp-container--cabinet {
        max-width: 960px;
        margin-bottom: 2rem;
    }
    .lp-f1 .lp-auth-panel {
        padding: 1.25rem 1.25rem 1.5rem;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-auth-panel { padding: 1.5rem 1.75rem 1.75rem; }
    }
    .lp-f1 .lp-auth-footer {
        padding: 1rem 1.25rem;
        border-top: 4px solid var(--lp-ink);
        text-align: center;
        font-size: 0.6875rem;
        font-weight: 800;
        text-transform: uppercase;
        background: #fbfbfb;
    }
    .lp-f1 .lp-auth-footer a {
        color: var(--lp-ink);
        text-decoration: underline;
        text-underline-offset: 3px;
    }
    .lp-f1 .lp-auth-title {
        font-size: 1.125rem;
        font-weight: 900;
        text-transform: uppercase;
        margin: 0 0 1rem 0;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    .lp-f1 .lp-auth-lead {
        font-size: 0.8125rem;
        font-weight: 500;
        color: #333;
        line-height: 1.55;
        margin: 0 0 1.25rem 0;
    }
    .lp-f1 .lp-auth-panel label.block,
    .lp-f1 .lp-cabinet-main label.block {
        font-size: 0.625rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--lp-ink) !important;
    }
    .lp-f1 .lp-auth-panel input[type="email"],
    .lp-f1 .lp-auth-panel input[type="password"],
    .lp-f1 .lp-auth-panel input[type="text"],
    .lp-f1 .lp-cabinet-main input[type="email"],
    .lp-f1 .lp-cabinet-main input[type="password"],
    .lp-f1 .lp-cabinet-main input[type="text"] {
        width: 100%;
        margin-top: 0.35rem;
        padding: 0.65rem 0.75rem;
        border: 3px solid var(--lp-ink) !important;
        border-radius: 0 !important;
        font-size: 0.9375rem;
        font-weight: 600;
        background: #fff;
        box-shadow: none !important;
    }
    .lp-f1 .lp-auth-panel input:focus,
    .lp-f1 .lp-cabinet-main input:focus {
        outline: 2px solid var(--lp-orange);
        outline-offset: 2px;
    }
    .lp-f1 .lp-auth-panel button[type="submit"]:not(.lp-danger-outline),
    .lp-f1 .lp-cabinet-main button[type="submit"]:not(.lp-danger-outline) {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        padding: 0.75rem 1.25rem !important;
        background: var(--lp-orange) !important;
        color: #fff !important;
        border: 3px solid var(--lp-ink) !important;
        border-radius: 0 !important;
        font-weight: 900 !important;
        text-transform: uppercase !important;
        font-size: 0.75rem !important;
        letter-spacing: 0.04em !important;
        cursor: pointer;
        box-shadow: none !important;
    }
    .lp-f1 .lp-auth-panel button[type="submit"]:not(.lp-danger-outline):hover,
    .lp-f1 .lp-cabinet-main button[type="submit"]:not(.lp-danger-outline):hover {
        background: #E03E00 !important;
    }
    .lp-f1 .lp-auth-actions {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 1.25rem;
    }
    @media (min-width: 520px) {
        .lp-f1 .lp-auth-actions {
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem 1rem;
        }
        .lp-f1 .lp-auth-actions .lp-auth-secondary {
            margin-right: auto;
        }
    }
    .lp-f1 .lp-verify-actions {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 1.25rem;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-verify-actions {
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
    }
    .lp-f1 .lp-auth-secondary {
        font-size: 0.6875rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--lp-ink);
        text-decoration: underline;
        text-underline-offset: 3px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        font-family: inherit;
    }
    .lp-f1 .lp-auth-secondary:hover { color: var(--lp-orange); }
    .lp-f1 .lp-auth-panel ul.text-red-600,
    .lp-f1 .lp-cabinet-main ul.text-red-600 {
        list-style: none;
        padding: 0.5rem 0.75rem;
        margin: 0.5rem 0 0 0;
        border: 2px solid var(--lp-ink);
        background: #ffeaea;
        font-size: 0.75rem;
        font-weight: 700;
        color: #7f1d1d !important;
    }
    .lp-f1 .lp-auth-panel .text-green-600,
    .lp-f1 .lp-cabinet-main .text-green-600 {
        padding: 0.65rem 0.75rem;
        border: 2px solid var(--lp-ink);
        background: #e8f5e9;
        font-size: 0.8125rem;
        font-weight: 700;
        color: #1b5e20 !important;
    }
    .lp-f1 .lp-checkbox-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    .lp-f1 .lp-checkbox-row input[type="checkbox"] {
        width: 1.1rem;
        height: 1.1rem;
        border: 2px solid var(--lp-ink);
        accent-color: var(--lp-orange);
    }
    .lp-f1 .lp-checkbox-row span {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #333;
    }
    .lp-f1 .lp-checkbox-row--wrap {
        align-items: flex-start;
    }
    .lp-f1 .lp-checkbox-row--wrap input[type="checkbox"] {
        margin-top: 0.2rem;
    }
    .lp-f1 .lp-checkbox-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #222;
        line-height: 1.45;
        cursor: pointer;
    }
    .lp-f1 .lp-checkbox-label a {
        color: var(--lp-orange);
        font-weight: 800;
        text-decoration: underline;
        text-underline-offset: 3px;
    }
    .lp-f1 .lp-checkbox-label a:hover {
        color: #c03a00;
    }
    .lp-f1 .lp-cabinet-header { flex-wrap: wrap; }
    .lp-f1 .lp-cabinet-header__row {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    @media (min-width: 768px) {
        .lp-f1 .lp-cabinet-header__row { flex-wrap: nowrap; }
    }
    .lp-f1 .lp-cabinet-header__brand { text-decoration: none; color: inherit; }
    .lp-f1 .lp-cabinet-header__tools {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
        margin-left: auto;
    }
    .lp-f1 .lp-cab-nav {
        display: none;
        flex-wrap: wrap;
        gap: 0.35rem;
        justify-content: center;
        flex: 1 1 auto;
        min-width: 0;
    }
    @media (min-width: 768px) {
        .lp-f1 .lp-cab-nav {
            display: flex;
            margin-left: 0.5rem;
            margin-right: 0.5rem;
        }
    }
    .lp-f1 .lp-cab-nav__link {
        font-size: 0.625rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.45rem 0.6rem;
        border: 2px solid var(--lp-ink);
        text-decoration: none;
        color: var(--lp-ink);
        background: #fff;
    }
    .lp-f1 .lp-cab-nav__link:hover { background: #f5f5f5; }
    .lp-f1 .lp-cab-nav__link--active {
        background: var(--lp-ink);
        color: #fff;
    }
    .lp-f1 .lp-header-burger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border: 2px solid var(--lp-ink);
        background: #fff;
        cursor: pointer;
        padding: 0;
    }
    @media (min-width: 768px) {
        .lp-f1 .lp-header-burger { display: none; }
    }
    .lp-f1 .lp-user-trigger {
        font-size: 0.6875rem;
        font-weight: 800;
        text-transform: uppercase;
        padding: 0.5rem 0.75rem;
        border: 2px solid var(--lp-ink);
        background: #fff;
        cursor: pointer;
        max-width: 11rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .lp-f1 .lp-dropdown-panel {
        border-radius: 0 !important;
        border: 3px solid var(--lp-ink) !important;
        box-shadow: 6px 6px 0 var(--lp-ink) !important;
    }
    .lp-f1 .lp-dropdown-panel a {
        font-size: 0.6875rem !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
    }
    .lp-f1 .lp-drawer {
        border-right: 4px solid var(--lp-ink);
        box-shadow: 8px 0 0 var(--lp-ink);
    }
    .lp-f1 .lp-drawer-nav a {
        display: block;
        padding: 0.85rem 1rem;
        font-size: 0.8125rem;
        font-weight: 800;
        text-transform: uppercase;
        text-decoration: none;
        border: 2px solid var(--lp-ink);
        margin-bottom: 0.5rem;
        color: var(--lp-ink);
        background: #fff;
    }
    .lp-f1 .lp-drawer-nav a:hover { background: #f5f5f5; }
    .lp-f1 .lp-drawer-nav a.lp-cab-nav__link--active {
        background: var(--lp-ink);
        color: #fff;
    }
    .lp-f1 .lp-cabinet-main { padding: 1rem 1.1rem 1.5rem; }
    @media (min-width: 480px) {
        .lp-f1 .lp-cabinet-main { padding: 1.25rem 1.5rem 2rem; }
    }
    .lp-f1 .lp-page-title {
        font-size: 1.125rem;
        font-weight: 900;
        text-transform: uppercase;
        margin: 0 0 1.25rem 0;
        letter-spacing: -0.02em;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-page-title { font-size: 1.375rem; }
    }
    .lp-f1 .lp-card {
        background: #fff;
        border: 4px solid var(--lp-ink);
        box-shadow: 8px 8px 0 var(--lp-ink);
        margin-bottom: 1.25rem;
    }
    .lp-f1 .lp-card__head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.85rem 1rem;
        border-bottom: 4px solid var(--lp-ink);
        background: #fbfbfb;
    }
    .lp-f1 .lp-card__head-note {
        font-size: 0.6875rem;
        font-weight: 600;
        color: #444;
        text-align: right;
    }
    .lp-f1 .lp-card__body { padding: 1rem 1rem 1.15rem; }
    @media (min-width: 480px) {
        .lp-f1 .lp-card__body { padding: 1.15rem 1.25rem 1.35rem; }
    }
    .lp-f1 .lp-field-label {
        font-size: 0.625rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #444;
        margin-bottom: 0.35rem;
    }
    .lp-f1 .lp-textarea {
        display: block;
        width: 100%;
        border: 3px solid var(--lp-ink);
        padding: 0.65rem 0.75rem;
        font-family: ui-monospace, "Cascadia Code", monospace;
        font-size: 0.6875rem;
        line-height: 1.45;
        background: #f9f9f9;
        color: var(--lp-ink);
        resize: vertical;
        min-height: 5rem;
    }

    .lp-f1 .lp-copy-row {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
        align-items: flex-start;
    }
    .lp-f1 .lp-copy-hint {
        font-size: 0.75rem;
        color: #475569;
        font-weight: 600;
        line-height: 1.35;
    }
    .lp-f1 .lp-btn.lp-btn--copy {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1rem;
        border: 3px solid var(--lp-ink);
        background: #fff;
        font-weight: 900;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
    }
    .lp-f1 .lp-btn.lp-btn--copy:hover {
        background: var(--lp-ink);
        color: #fff;
    }
    .lp-f1 .lp-btn.lp-btn--copy.lp-btn--copied {
        background: var(--lp-orange);
        color: #fff;
    }
    .lp-f1 .lp-btn.lp-btn--copy.lp-btn--copied:hover {
        background: #E03E00;
        color: #fff;
    }

    .lp-f1 .lp-steps { margin-top: 0.35rem; }
    .lp-f1 .lp-step {
        display: grid;
        grid-template-columns: 2.25rem minmax(0, 1fr);
        gap: 0.75rem;
        align-items: start;
        padding: 0.85rem 0.9rem;
        border: 3px solid var(--lp-ink);
        background: #fffef5;
    }
    .lp-f1 .lp-step + .lp-step { margin-top: 0.75rem; }
    .lp-f1 .lp-step__num {
        width: 2.25rem;
        height: 2.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 3px solid var(--lp-ink);
        background: var(--lp-orange);
        color: #fff;
        font-weight: 900;
        font-variant-numeric: tabular-nums;
        line-height: 1;
    }
    .lp-f1 .lp-step__title {
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        font-size: 0.75rem;
        margin: 0 0 0.35rem 0;
    }
    .lp-f1 .lp-step__text {
        font-size: 0.875rem;
        line-height: 1.45;
        font-weight: 600;
        color: #1f2937;
    }

    .lp-f1 .lp-store-grid {
        margin-top: 0.6rem;
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    @media (min-width: 560px) {
        .lp-f1 .lp-store-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (min-width: 860px) {
        .lp-f1 .lp-store-grid { grid-template-columns: 1fr 1fr 1fr; }
    }
    .lp-f1 .lp-store-btn {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        border: 3px solid var(--lp-ink);
        box-shadow: 6px 6px 0 var(--lp-ink);
        padding: 0.85rem 0.95rem;
        text-decoration: none;
        color: var(--lp-ink);
        background: #fff;
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .lp-f1 .lp-store-btn:hover {
        transform: translate(1px, 1px);
        box-shadow: 4px 4px 0 var(--lp-ink);
    }
    .lp-f1 .lp-store-btn__icon {
        width: 2.1rem;
        height: 2.1rem;
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--lp-ink);
        background: #fbfbfb;
    }
    .lp-f1 .lp-store-btn__icon svg { width: 1.45rem; height: 1.45rem; }
    .lp-f1 .lp-store-btn__kicker {
        display: block;
        font-size: 0.625rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #475569;
        margin-bottom: 0.1rem;
    }
    .lp-f1 .lp-store-btn__title {
        display: block;
        font-size: 0.95rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: -0.01em;
    }
    .lp-f1 .lp-empty {
        text-align: center;
        padding: 2rem 1.25rem;
        border: 4px dashed var(--lp-ink);
        background: #fffef5;
    }
    .lp-f1 .lp-empty p { margin: 0; font-weight: 600; color: #222; }
    .lp-f1 .lp-empty p + p { margin-top: 0.65rem; font-size: 0.8125rem; font-weight: 500; color: #555; }
    .lp-f1 .lp-empty .lp-btn {
        display: inline-block;
        margin-top: 1.25rem;
        padding: 0.65rem 1.15rem;
        background: var(--lp-orange);
        color: #fff;
        font-size: 0.6875rem;
        font-weight: 900;
        text-transform: uppercase;
        text-decoration: none;
        border: 3px solid var(--lp-ink);
    }
    .lp-f1 .lp-empty .lp-btn:hover { background: #E03E00; color: #fff; }
    .lp-f1 .lp-muted { font-size: 0.75rem; color: #555; font-weight: 500; margin-top: 0.35rem; }
    .lp-f1 .lp-mono { font-family: ui-monospace, monospace; font-weight: 700; font-size: 0.8125rem; }
    .lp-f1 .lp-badge-pill {
        display: inline-block;
        padding: 0.2rem 0.45rem;
        font-size: 0.5625rem;
        font-weight: 900;
        text-transform: uppercase;
        border: 2px solid var(--lp-ink);
    }
    .lp-f1 .lp-badge-pill--ok { background: #d4edda; }
    .lp-f1 .lp-badge-pill--bad { background: #f8d7da; }
    .lp-f1 .lp-stack > * + * { margin-top: 1rem; }
    .lp-f1 .lp-warn-box {
        padding: 0.75rem 1rem;
        border: 3px solid var(--lp-ink);
        background: #fff8e6;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #222;
    }
    .lp-f1 .lp-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border: 4px solid var(--lp-ink);
        box-shadow: 8px 8px 0 var(--lp-ink);
    }
    .lp-f1 .lp-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.8125rem;
    }
    .lp-f1 .lp-table thead tr {
        background: var(--lp-ink);
        color: #fff;
    }
    .lp-f1 .lp-table th {
        text-align: left;
        padding: 0.65rem 0.75rem;
        font-size: 0.5625rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    .lp-f1 .lp-table td {
        padding: 0.65rem 0.75rem;
        border-bottom: 2px solid var(--lp-ink);
        font-weight: 600;
    }
    .lp-f1 .lp-table tbody tr:last-child td { border-bottom: none; }
    .lp-f1 .lp-table tbody tr:hover { background: #f9f9f9; }
    .lp-f1 .lp-purchase-cards > * + * { margin-top: 0.75rem; }
    .lp-f1 .lp-purchase-card {
        border: 3px solid var(--lp-ink);
        padding: 1rem;
        background: #fff;
    }
    .lp-f1 .lp-profile-block {
        border: 4px solid var(--lp-ink);
        box-shadow: 8px 8px 0 var(--lp-ink);
        padding: 1.15rem 1.1rem;
        margin-bottom: 1.25rem;
        background: #fff;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-profile-block { padding: 1.25rem 1.35rem; }
    }
    .lp-f1 .lp-profile-block h2 {
        font-size: 1rem;
        font-weight: 900;
        text-transform: uppercase;
        margin: 0;
        letter-spacing: -0.02em;
    }
    .lp-f1 .lp-profile-block header p {
        margin: 0.5rem 0 0 0;
        font-size: 0.8125rem;
        font-weight: 500;
        color: #444;
    }
    .lp-f1 .lp-profile-block section form { margin-top: 1rem; }
    .lp-f1 .lp-dl-grid {
        display: grid;
        gap: 0.85rem;
        margin-top: 1rem;
    }
    @media (min-width: 520px) {
        .lp-f1 .lp-dl-grid { grid-template-columns: 1fr 1fr; }
    }
    .lp-f1 .lp-dl-grid--account {
        grid-template-columns: 1fr;
    }
    @media (min-width: 520px) {
        .lp-f1 .lp-dl-grid--account {
            grid-template-columns: 1fr 1fr;
            align-items: stretch;
        }
    }
    .lp-f1 .lp-dl-grid--account > div {
        min-width: 0;
    }
    .lp-f1 .lp-dl-grid__action {
        margin: 0.2rem 0 0 0;
    }
    .lp-f1 .lp-cabinet-main .lp-account-verify-btn {
        display: inline-flex !important;
        justify-content: center !important;
        align-items: center !important;
        padding: 0.1rem 0.5rem !important;
        background: var(--lp-orange) !important;
        color: #fff !important;
        border: 3px solid var(--lp-ink) !important;
        border-radius: 0 !important;
        font-weight: 900 !important;
        text-transform: uppercase !important;
        font-size: 0.75rem !important;
        letter-spacing: 0.04em !important;
        cursor: pointer !important;
        font-family: inherit;
        box-shadow: none !important;
    }
    .lp-f1 .lp-cabinet-main .lp-account-verify-btn:hover {
        background: #E03E00 !important;
    }
    .lp-f1 .lp-verify-email-modal-address {
        margin-top: 0.35rem;
        padding: 0.5rem 0.75rem;
        font-family: ui-monospace, monospace;
        font-size: 0.875rem;
        font-weight: 700;
        word-break: break-all;
        border: 3px solid var(--lp-ink);
        background: #f8fafc;
    }
    .lp-f1 .lp-dl-grid dt {
        font-size: 0.5625rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #666;
    }
    .lp-f1 .lp-dl-grid dd {
        margin: 0.2rem 0 0 0;
        font-size: 0.875rem;
        font-weight: 700;
        color: var(--lp-ink);
    }
    .lp-f1 .lp-cabinet-main button.lp-danger-outline {
        background: #fff !important;
        color: #b91c1c !important;
        border: 3px solid var(--lp-ink) !important;
        border-radius: 0 !important;
    }
    .lp-f1 .lp-cabinet-main button.lp-danger-outline:hover {
        background: #fef2f2 !important;
    }
    .lp-f1 .lp-cabinet-main button.lp-secondary-outline {
        background: #fff !important;
        color: var(--lp-ink) !important;
        border: 3px solid var(--lp-ink) !important;
        border-radius: 0 !important;
    }
    .lp-f1 .lp-pagination-brutal { margin-top: 1.25rem; }
    .lp-f1 .lp-pagination-brutal nav {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        align-items: center;
    }
    .lp-f1 .lp-pagination-brutal a,
    .lp-f1 .lp-pagination-brutal span[aria-current="page"] span,
    .lp-f1 .lp-pagination-brutal span[aria-disabled="true"] span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.25rem;
        padding: 0.35rem 0.65rem;
        border: 2px solid var(--lp-ink);
        font-size: 0.75rem;
        font-weight: 800;
        text-decoration: none;
        color: var(--lp-ink);
        background: #fff;
    }
    .lp-f1 .lp-pagination-brutal a:hover { background: #f5f5f5; }
    .lp-f1 .lp-pagination-brutal span[aria-current="page"] span {
        background: var(--lp-ink);
        color: #fff;
    }
    .lp-f1 .lp-modal-brutal {
        border: 4px solid var(--lp-ink) !important;
        border-radius: 0 !important;
        box-shadow: 12px 12px 0 var(--lp-ink) !important;
    }
    .lp-f1 .lp-profile-block section header h2.text-lg {
        font-size: 0.9375rem !important;
        font-weight: 900 !important;
        text-transform: uppercase !important;
        letter-spacing: -0.02em !important;
        color: var(--lp-ink) !important;
    }
    .lp-f1 .lp-profile-block section header p.text-sm {
        color: #444 !important;
        font-weight: 500 !important;
    }

    .lp-f1 .lp-container--agreement { max-width: 720px; }
    @media (min-width: 768px) {
        .lp-f1 .lp-container--agreement { max-width: 760px; }
    }
    .lp-f1 .lp-agreement-hero {
        padding: 1.5rem 1.25rem;
        border-bottom: 4px solid var(--lp-ink);
        background: #fffde7;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-agreement-hero { padding: 1.75rem 1.75rem; }
    }
    .lp-f1 .lp-agreement-hero h1 {
        font-size: 1.125rem;
        font-weight: 900;
        text-transform: uppercase;
        margin: 0 0 0.5rem 0;
        line-height: 1.2;
        letter-spacing: -0.02em;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-agreement-hero h1 { font-size: 1.35rem; }
    }
    .lp-f1 .lp-agreement-hero .lp-agreement-sub {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #333;
        margin: 0;
        line-height: 1.5;
    }
    .lp-f1 .lp-agreement-hero .lp-agreement-meta {
        margin-top: 0.85rem;
        font-size: 0.6875rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #444;
    }
    .lp-f1 .lp-agreement-section {
        padding: 1.25rem 1.25rem 1.35rem;
        border-bottom: 4px solid var(--lp-ink);
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-agreement-section { padding: 1.35rem 1.75rem 1.5rem; }
    }
    .lp-f1 .lp-agreement-section:last-of-type { border-bottom: none; }
    .lp-f1 .lp-agreement-section h2 {
        font-size: 1rem;
        font-weight: 900;
        text-transform: uppercase;
        margin: 0 0 0.85rem 0;
        letter-spacing: -0.02em;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-agreement-section h2 { font-size: 1.125rem; }
    }
    .lp-f1 .lp-agreement-section h3 {
        font-size: 0.8125rem;
        font-weight: 900;
        text-transform: uppercase;
        margin: 1rem 0 0.5rem 0;
        letter-spacing: 0.02em;
    }
    .lp-f1 .lp-agreement-section p,
    .lp-f1 .lp-agreement-section li {
        font-size: 0.875rem;
        font-weight: 500;
        line-height: 1.55;
        color: #222;
        margin: 0 0 0.65rem 0;
    }
    .lp-f1 .lp-agreement-section p:last-child { margin-bottom: 0; }
    .lp-f1 .lp-agreement-section ol {
        margin: 0 0 0.5rem 0;
        padding-left: 1.15rem;
    }
    .lp-f1 .lp-agreement-section ol li { margin-bottom: 0.5rem; }
    .lp-f1 .lp-agreement-section ol ul {
        margin-top: 0.4rem;
        margin-bottom: 0.35rem;
    }
    .lp-f1 .lp-agreement-section ul {
        list-style: none;
        margin: 0.35rem 0 0 0;
        padding: 0;
    }
    .lp-f1 .lp-agreement-section ul li {
        position: relative;
        padding-left: 1rem;
        margin-bottom: 0.35rem;
    }
    .lp-f1 .lp-agreement-section ul li::before {
        content: "—";
        position: absolute;
        left: 0;
        font-weight: 900;
        color: var(--lp-orange);
    }
    .lp-f1 .lp-agreement-requisites span {
        display: block;
        margin-bottom: 0.35rem;
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.55;
    }
</style>

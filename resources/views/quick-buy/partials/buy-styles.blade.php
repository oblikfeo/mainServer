<style>
    /* Попапы вне .lp-container — задаём дизайн-систему явно */
    .lp-buy-modal {
        --mock-primary: #ff4d00;
        --mock-dark: #1a1a1a;
        --mock-accent: #bff000;
        --mock-secondary: #2d31fa;
        --mock-border: 3px solid #1a1a1a;
        --mock-shadow: 8px 8px 0 #1a1a1a;
        --mock-cream: #fdf9f0;

        position: fixed;
        inset: 0;
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(26, 26, 26, 0.55);
        backdrop-filter: blur(4px);
    }
    .lp-buy-modal.lp-buy-modal--open {
        display: flex;
    }

    .lp-buy-modal__panel {
        width: min(100%, 440px);
        background: var(--mock-cream);
        border: var(--mock-border);
        box-shadow: var(--mock-shadow);
        padding: 28px 24px 22px;
        position: relative;
        animation: lp-buy-modal-in 0.22s ease-out;
    }
    @keyframes lp-buy-modal-in {
        from { opacity: 0; transform: translate(6px, 6px); }
        to { opacity: 1; transform: translate(0, 0); }
    }
    @media (prefers-reduced-motion: reduce) {
        .lp-buy-modal__panel { animation: none; }
    }

    .lp-buy-modal__close {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 40px;
        height: 40px;
        border: var(--mock-border);
        background: #fff;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        font-weight: 800;
        color: var(--mock-dark);
        transition: transform 0.2s, box-shadow 0.2s, background 0.2s;
    }
    .lp-buy-modal__close:hover {
        transform: translate(-2px, -2px);
        box-shadow: 4px 4px 0 var(--mock-dark);
        background: var(--mock-accent);
    }

    .lp-buy-modal__kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--mock-accent);
        color: var(--mock-dark);
        padding: 8px 14px;
        font-weight: 800;
        font-size: 11px;
        text-transform: uppercase;
        margin-bottom: 16px;
        border: var(--mock-border);
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        letter-spacing: 0.06em;
    }
    .lp-buy-modal__kicker-dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        background: var(--mock-primary);
        border: 2px solid var(--mock-dark);
        flex-shrink: 0;
    }

    .lp-buy-modal__title {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 32px;
        line-height: 1;
        font-weight: 800;
        text-transform: uppercase;
        margin: 0 0 12px;
        color: var(--mock-dark);
        letter-spacing: -0.02em;
        padding-right: 36px;
    }
    .lp-buy-modal__title-em {
        font-family: "Playfair Display", Georgia, serif;
        font-style: italic;
        font-weight: 700;
        color: var(--mock-primary);
    }
    @media (min-width: 768px) {
        .lp-buy-modal__title { font-size: 38px; }
    }

    .lp-buy-modal__sub {
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 15px;
        line-height: 1.5;
        color: #4b5563;
        margin: 0 0 18px;
    }

    .lp-buy-modal__amount-wrap {
        background: #fff;
        border: var(--mock-border);
        box-shadow: 4px 4px 0 var(--mock-dark);
        padding: 14px 16px;
        margin-bottom: 18px;
    }
    .lp-buy-modal__amount-label {
        display: block;
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #64748b;
        margin-bottom: 4px;
    }
    .lp-buy-modal__amount {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 36px;
        font-weight: 800;
        margin: 0;
        color: var(--mock-primary);
        line-height: 1;
        letter-spacing: -0.02em;
    }

    .lp-buy-modal__field-label {
        display: block;
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--mock-dark);
        margin-bottom: 8px;
    }

    .lp-buy-email-modal-form input[type="email"] {
        width: 100%;
        padding: 14px 16px;
        border: var(--mock-border);
        background: #fff;
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 16px;
        color: var(--mock-dark);
        margin-bottom: 12px;
        box-sizing: border-box;
        transition: box-shadow 0.15s;
    }
    .lp-buy-email-modal-form input[type="email"]:focus {
        outline: none;
        box-shadow: 4px 4px 0 var(--mock-primary);
    }

    .lp-f1 .lp-buy-pay-btn,
    .lp-buy-modal .lp-buy-pay-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        margin-top: 4px;
        padding: 15px 18px;
        border: var(--mock-border);
        background: var(--mock-primary);
        color: #fff;
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .lp-f1 .lp-buy-pay-btn {
        margin-top: 16px;
    }
    .lp-f1 .lp-buy-pay-btn:hover:not(:disabled),
    .lp-buy-modal .lp-buy-pay-btn:hover:not(:disabled) {
        transform: translate(-2px, -2px);
        box-shadow: var(--mock-shadow);
    }
    .lp-f1 .lp-buy-pay-btn:disabled,
    .lp-buy-modal .lp-buy-pay-btn:disabled {
        opacity: 0.65;
        cursor: wait;
    }

    .lp-buy-modal__qr-wrap {
        display: flex;
        justify-content: center;
        margin-bottom: 16px;
        padding: 16px;
        background: #fff;
        border: var(--mock-border);
        box-shadow: 4px 4px 0 var(--mock-dark);
    }
    .lp-buy-modal__qr-wrap canvas,
    .lp-buy-modal__qr-wrap img {
        display: block;
        max-width: 220px;
        width: 100%;
        height: auto;
    }

    .lp-buy-modal__status {
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 14px;
        font-weight: 700;
        text-align: center;
        color: var(--mock-dark);
        margin: 0;
        padding: 10px 12px;
        background: var(--mock-accent);
        border: var(--mock-border);
    }
    .lp-buy-modal__status--error {
        background: #fff;
        color: #b91c1c;
        text-align: left;
        margin-bottom: 10px;
        padding: 10px 12px;
    }

    .lp-buy-modal__hint {
        margin-top: 14px;
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 12px;
        line-height: 1.45;
        color: #64748b;
        text-align: center;
    }
    .lp-buy-modal__hint a {
        color: var(--mock-dark);
        font-weight: 800;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .lp-buy-done-box {
        background: var(--mock-cream, #fdf9f0);
        border: var(--mock-border, 3px solid #1a1a1a);
        box-shadow: var(--mock-shadow, 8px 8px 0 #1a1a1a);
        padding: 24px;
        margin-bottom: 20px;
    }
    .lp-buy-done-box__label {
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        margin-bottom: 8px;
    }
    .lp-buy-done-box__url {
        display: block;
        word-break: break-all;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 13px;
        line-height: 1.45;
        color: #0f172a;
        margin-bottom: 12px;
    }
    .lp-buy-done-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 16px;
    }
    .lp-buy-done-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 18px;
        border: 3px solid #1a1a1a;
        background: #ff4d00;
        color: #fff;
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
        text-decoration: none;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .lp-buy-done-btn:hover {
        transform: translate(-2px, -2px);
        box-shadow: 8px 8px 0 #1a1a1a;
        color: #fff;
    }
    .lp-buy-done-btn--secondary {
        background: #fff;
        color: #1a1a1a;
    }
    .lp-buy-done-creds {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 14px;
        line-height: 1.6;
        color: #0f172a;
    }
    .lp-buy-wait {
        text-align: center;
        padding: 40px 20px;
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 16px;
        color: #475569;
    }

    .lp-buy-test-btn {
        margin: 0;
        padding: 0;
        border: 0;
        background: none;
        font: inherit;
        font-size: 11px;
        line-height: 1;
        color: inherit;
        opacity: 0.22;
        cursor: pointer;
        transition: opacity 0.15s;
    }
    .lp-buy-test-btn:hover:not(:disabled) {
        opacity: 0.45;
    }
    .lp-buy-test-btn:disabled {
        opacity: 0.15;
        cursor: wait;
    }
</style>

<style>
    .lp-f1 .lp-buy-pay-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        margin-top: 16px;
        padding: 14px 18px;
        border: var(--mock-border);
        background: var(--mock-primary);
        color: #fff;
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .lp-f1 .lp-buy-pay-btn:hover:not(:disabled) {
        transform: translate(-2px, -2px);
        box-shadow: var(--mock-shadow);
    }
    .lp-f1 .lp-buy-pay-btn:disabled {
        opacity: 0.65;
        cursor: wait;
    }

    .lp-buy-modal {
        position: fixed;
        inset: 0;
        z-index: 100;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(15, 23, 42, 0.72);
    }
    .lp-buy-modal.lp-buy-modal--open {
        display: flex;
    }
    .lp-buy-modal__panel {
        width: min(100%, 420px);
        background: #fff;
        border: var(--mock-border);
        box-shadow: var(--mock-shadow);
        padding: 28px 24px 24px;
        position: relative;
    }
    .lp-buy-modal__close {
        position: absolute;
        top: 12px;
        right: 12px;
        width: 36px;
        height: 36px;
        border: var(--mock-border);
        background: #fff;
        font-size: 20px;
        line-height: 1;
        cursor: pointer;
        font-weight: 800;
    }
    .lp-buy-modal__title {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 22px;
        font-weight: 800;
        text-transform: uppercase;
        margin: 0 0 8px;
        color: var(--mock-dark);
    }
    .lp-buy-modal__sub {
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 14px;
        line-height: 1.5;
        color: #475569;
        margin: 0 0 18px;
    }
    .lp-buy-modal__amount {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 32px;
        font-weight: 800;
        margin: 0 0 16px;
        color: var(--mock-dark);
    }
    .lp-buy-modal__qr-wrap {
        display: flex;
        justify-content: center;
        margin-bottom: 16px;
    }
    .lp-buy-modal__qr-wrap canvas,
    .lp-buy-modal__qr-wrap img {
        border: var(--mock-border);
        background: #fff;
        padding: 8px;
        max-width: 240px;
        width: 100%;
        height: auto;
    }
    .lp-buy-modal__status {
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 14px;
        font-weight: 700;
        text-align: center;
        color: #334155;
        margin: 0;
    }
    .lp-buy-modal__status--error {
        color: #b91c1c;
    }
    .lp-buy-modal__hint {
        margin-top: 12px;
        font-size: 12px;
        line-height: 1.45;
        color: #64748b;
        text-align: center;
    }

    .lp-buy-done-box {
        background: #fff;
        border: var(--mock-border);
        box-shadow: var(--mock-shadow);
        padding: 24px;
        margin-bottom: 20px;
    }
    .lp-buy-done-box__label {
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
        border: var(--mock-border);
        background: var(--mock-primary);
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
        box-shadow: var(--mock-shadow);
        color: #fff;
    }
    .lp-buy-done-btn--secondary {
        background: #fff;
        color: var(--mock-dark);
    }
    .lp-buy-done-creds {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 14px;
        line-height: 1.6;
        color: #0f172a;
    }
    .lp-buy-email-form {
        margin-top: 24px;
        padding-top: 24px;
        border-top: var(--mock-border);
    }
    .lp-buy-email-form input[type="email"] {
        width: 100%;
        padding: 12px 14px;
        border: var(--mock-border);
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 15px;
        margin-bottom: 10px;
    }
    .lp-buy-email-form--modal {
        margin-top: 0;
        padding-top: 0;
        border-top: none;
    }
    .lp-buy-email-modal-form input[type="email"] {
        width: 100%;
        padding: 12px 14px;
        border: var(--mock-border);
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 15px;
        margin-bottom: 10px;
    }
    .lp-buy-email-modal-form .lp-buy-pay-btn {
        width: 100%;
        margin-top: 4px;
    }
    .lp-buy-wait {
        text-align: center;
        padding: 40px 20px;
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 16px;
        color: #475569;
    }
</style>

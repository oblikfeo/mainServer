<style>
    .lp-f1 .lp-container--tutorial {
        max-width: 480px;
        min-height: 100dvh;
        display: flex;
        flex-direction: column;
    }

    .lp-f1 .lp-tutorial {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .lp-f1 .lp-tutorial__progress {
        display: flex;
        gap: 0.35rem;
        padding: 0.85rem 1rem;
        border-bottom: 3px solid var(--lp-ink);
        background: #fff;
    }

    .lp-f1 .lp-tutorial__dot {
        flex: 1;
        height: 4px;
        background: #e2e8f0;
        border: 2px solid var(--lp-ink);
        transition: background 0.3s ease;
    }

    .lp-f1 .lp-tutorial__dot--active {
        background: var(--lp-orange);
    }

    .lp-f1 .lp-tutorial__dot--done {
        background: var(--lp-ink);
    }

    .lp-f1 .lp-tutorial__stage {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
        position: relative;
        overflow: hidden;
    }

    .lp-f1 .lp-tutorial__slide {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 1.5rem 1.25rem 1rem;
        min-height: 0;
        transition: opacity 0.35s ease, transform 0.35s ease;
    }

    .lp-f1 .lp-tutorial__slide--out {
        opacity: 0;
        transform: translateY(-12px);
        pointer-events: none;
        position: absolute;
        inset: 0;
    }

    .lp-f1 .lp-tutorial__slide--in {
        opacity: 1;
        transform: translateY(0);
    }

    .lp-f1 .lp-tutorial__slide--hidden {
        opacity: 0;
        transform: translateY(12px);
        pointer-events: none;
        position: absolute;
        inset: 0;
    }

    .lp-f1 .lp-tutorial__icon {
        width: 3.5rem;
        height: 3.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        border: 3px solid var(--lp-ink);
        background: #fff8e6;
        box-shadow: 4px 4px 0 var(--lp-ink);
        margin-bottom: 1.25rem;
        flex-shrink: 0;
    }

    .lp-f1 .lp-tutorial__step-label {
        font-size: 0.625rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--lp-orange);
        margin-bottom: 0.5rem;
    }

    .lp-f1 .lp-tutorial__title {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 1.375rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: -0.02em;
        line-height: 1.15;
        margin: 0 0 0.85rem 0;
        color: var(--lp-ink);
    }

    @media (min-width: 480px) {
        .lp-f1 .lp-tutorial__title {
            font-size: 1.625rem;
        }
    }

    .lp-f1 .lp-tutorial__text {
        font-size: 0.9375rem;
        font-weight: 500;
        line-height: 1.55;
        color: #333;
        margin: 0;
        flex: 1;
        overflow-wrap: anywhere;
    }

    .lp-f1 .lp-tutorial__text + .lp-tutorial__text {
        margin-top: 0.75rem;
    }

    .lp-f1 .lp-tutorial__hint {
        margin-top: 1rem;
        padding: 0.75rem 0.85rem;
        border: 3px solid var(--lp-ink);
        background: #e8f4f8;
        font-size: 0.8125rem;
        font-weight: 600;
        line-height: 1.45;
        color: #222;
    }

    .lp-f1 .lp-tutorial__actions {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        padding: 1rem 1.25rem max(1.25rem, env(safe-area-inset-bottom));
        border-top: 3px solid var(--lp-ink);
        background: #fff;
        flex-shrink: 0;
    }

    .lp-f1 .lp-tutorial__btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: 52px;
        padding: 0.85rem 1.25rem;
        border: 3px solid var(--lp-ink);
        font-family: inherit;
        font-size: 0.875rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s, box-shadow 0.15s;
        -webkit-tap-highlight-color: transparent;
        text-decoration: none;
        box-sizing: border-box;
    }

    .lp-f1 .lp-tutorial__btn--primary {
        background: var(--lp-orange);
        color: #fff;
        box-shadow: 4px 4px 0 var(--lp-ink);
    }

    .lp-f1 .lp-tutorial__btn--primary:hover {
        background: #E03E00;
    }

    .lp-f1 .lp-tutorial__btn--primary:active {
        transform: translate(2px, 2px);
        box-shadow: 2px 2px 0 var(--lp-ink);
    }

    .lp-f1 .lp-tutorial__btn--secondary {
        background: #fff;
        color: var(--lp-ink);
    }

    .lp-f1 .lp-tutorial__btn--secondary:hover {
        background: #f5f5f5;
    }

    .lp-f1 .lp-tutorial__btn--accent {
        background: var(--lp-mock-accent, #bff000);
        color: var(--lp-ink);
        box-shadow: 4px 4px 0 var(--lp-ink);
    }

    .lp-f1 .lp-tutorial__btn--accent:hover {
        transform: translate(-1px, -1px);
        box-shadow: 5px 5px 0 var(--lp-ink);
    }

    .lp-f1 .lp-tutorial__intro {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 2rem 1.25rem;
        text-align: center;
    }

    .lp-f1 .lp-tutorial__intro-badge {
        display: inline-block;
        align-self: center;
        background: var(--lp-ink);
        color: #fff;
        font-size: 0.625rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 0.35rem 0.65rem;
        margin-bottom: 1.25rem;
    }

    .lp-f1 .lp-tutorial__intro-title {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 1.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: -0.03em;
        line-height: 1.1;
        margin: 0 0 1rem 0;
    }

    .lp-f1 .lp-tutorial__intro-text {
        font-size: 0.9375rem;
        font-weight: 500;
        line-height: 1.5;
        color: #444;
        margin: 0;
    }

    .lp-f1 .lp-tutorial__done {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem 1.25rem;
        text-align: center;
    }

    .lp-f1 .lp-tutorial__done-icon {
        width: 4rem;
        height: 4rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        border: 3px solid var(--lp-ink);
        background: #d4edda;
        box-shadow: 6px 6px 0 var(--lp-ink);
        margin-bottom: 1.25rem;
    }

    .lp-f1 .lp-tutorial__ping-btn {
        margin-top: 1.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.85rem 1.5rem;
        background: var(--lp-orange);
        color: #fff;
        border: 3px solid var(--lp-ink);
        font-family: inherit;
        font-size: 0.8125rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        cursor: pointer;
        box-shadow: 4px 4px 0 var(--lp-ink);
        transition: background 0.2s, transform 0.15s;
        -webkit-tap-highlight-color: transparent;
    }

    .lp-f1 .lp-tutorial__ping-btn:hover {
        background: #E03E00;
    }

    .lp-f1 .lp-tutorial__ping-btn:active {
        transform: translate(2px, 2px);
        box-shadow: 2px 2px 0 var(--lp-ink);
    }

    .lp-f1 .lp-tutorial__ping-btn--loading {
        opacity: 0.7;
        pointer-events: none;
    }

    .lp-f1 .lp-tutorial__ping-result {
        margin-top: 0.85rem;
        font-size: 0.8125rem;
        font-weight: 700;
        line-height: 1.4;
    }

    .lp-f1 .lp-tutorial__ping-result--ok {
        color: #15803d;
    }

    .lp-f1 .lp-tutorial__ping-result--bad {
        color: #b91c1c;
    }

    @media (prefers-reduced-motion: reduce) {
        .lp-f1 .lp-tutorial__slide,
        .lp-f1 .lp-tutorial__dot {
            transition: none;
        }
    }
</style>

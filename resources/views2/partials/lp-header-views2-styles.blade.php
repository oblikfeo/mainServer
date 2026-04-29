<style>
    /* Макет: верстка/public/nadezhda.html — общая ширина блока */
    .lp-f1 .lp-container {
        max-width: 825px;
    }

    /* VPN-бейдж: оранжевый фон, белые буквы, чуть крупнее */
    .lp-f1 { --lp-mock-accent: #bff000; }
    .lp-f1 .lp-header.lp-header-v2 {
        flex-wrap: wrap;
        gap: 0.75rem 1rem;
        align-items: center;
    }
    .lp-f1 .lp-brand-line {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-shrink: 0;
    }
    /* Логотип как .logo в макете: Syne 800, tight tracking */
    .lp-f1 .lp-logo-heavy {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-weight: 800;
        font-size: 24px;
        text-transform: uppercase;
        letter-spacing: -1px;
        color: var(--lp-ink);
        line-height: 1;
    }
    @media (min-width: 768px) {
        .lp-f1 .lp-logo-heavy {
            font-size: 32px;
            letter-spacing: -2px;
        }
    }
    .lp-f1 .lp-logo-vpn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 0.8125rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #fff;
        background-color: var(--lp-orange);
        border: 3px solid var(--lp-ink);
        padding: 0.35rem 0.55rem;
        line-height: 1;
    }
    @media (min-width: 768px) {
        .lp-f1 .lp-logo-vpn {
            font-size: 0.9375rem;
            padding: 0.4rem 0.65rem;
        }
    }
    .lp-f1 .lp-header__nav {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 0.35rem 0.85rem;
        flex: 1 1 auto;
        min-width: min(100%, 12rem);
    }
    .lp-f1 .lp-header__nav a {
        font-size: 0.625rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--lp-ink);
        text-decoration: none;
        transition: color 0.2s;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-header__nav a { font-size: 0.6875rem; }
    }
    .lp-f1 .lp-header__nav a:hover {
        color: var(--lp-orange);
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .lp-f1 .lp-header-cta {
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        background: var(--lp-mock-accent);
        color: var(--lp-ink);
        padding: 10px 16px;
        border: 3px solid var(--lp-ink);
        box-shadow: 4px 4px 0 var(--lp-ink);
        font-weight: 800;
        text-transform: uppercase;
        font-size: 12px;
        line-height: 1.2;
        text-decoration: none;
        white-space: nowrap;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    @media (min-width: 768px) {
        .lp-f1 .lp-header-cta {
            padding: 12px 24px;
            font-size: 14px;
        }
    }
    .lp-f1 .lp-header-cta:hover {
        transform: translate(-2px, -2px);
        box-shadow: 6px 6px 0 var(--lp-ink);
        color: var(--lp-ink);
    }
    @media (prefers-reduced-motion: reduce) {
        .lp-f1 .lp-header-cta:hover {
            transform: none;
        }
    }

    .lp-f1 #about,
    .lp-f1 #support,
    .lp-f1 #tarify {
        scroll-margin-top: 0.75rem;
    }
</style>

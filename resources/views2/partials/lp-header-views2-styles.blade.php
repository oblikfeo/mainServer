<style>
    /* VPN-бейдж: вторичный цвет как в макете */
    .lp-f1 { --lp-indigo: #2d31fa; --lp-mock-accent: #bff000; }
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
    .lp-f1 .lp-logo-heavy {
        font-size: 1rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--lp-ink);
        line-height: 1;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-logo-heavy { font-size: 1.125rem; }
    }
    .lp-f1 .lp-logo-vpn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6875rem;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--lp-ink);
        background-color: var(--lp-indigo);
        border: 2px solid var(--lp-ink);
        padding: 0.3rem 0.45rem;
        line-height: 1;
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
    /* как в макете nadezhda.html: hover nav → primary + underline */
    .lp-f1 .lp-header__nav a:hover {
        color: var(--lp-orange);
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    /* как .btn-cta в макете: lime bg, 3px border, offset shadow, hover lift */
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

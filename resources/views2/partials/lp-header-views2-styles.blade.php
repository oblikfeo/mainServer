<style>
    .lp-f1 { --lp-indigo: #2d31fa; --lp-indigo-hover: #2529c7; }
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
        border-bottom: 2px solid transparent;
        padding-bottom: 0.1rem;
        transition: border-color 0.15s, color 0.15s;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-header__nav a { font-size: 0.6875rem; }
    }
    .lp-f1 .lp-header__nav a:hover {
        border-bottom-color: var(--lp-indigo);
        color: var(--lp-indigo);
    }
    .lp-f1 .lp-header-v2 .lp-login-btn {
        margin-left: auto;
    }

    /* Синяя CTA как на бейдже VPN (#2d31fa), ховер темнее — по тону оранжевой lp-cta-btn */
    .lp-f1 .lp-cta-blue {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--lp-indigo);
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
        transition: background 0.2s ease;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-cta-blue { padding: 1.5rem 1.75rem; font-size: 1.125rem; }
    }
    .lp-f1 .lp-cta-blue:hover {
        background: var(--lp-indigo-hover);
        color: #fff;
    }
    .lp-f1 button.lp-cta-blue { font-family: inherit; }

    html { scroll-behavior: smooth; }
    .lp-f1 #about,
    .lp-f1 #support {
        scroll-margin-top: 0.75rem;
    }
    .lp-f1 #tarify {
        scroll-margin-top: 0.75rem;
    }
</style>

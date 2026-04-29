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
        transition: opacity 0.15s;
    }
    @media (min-width: 480px) {
        .lp-f1 .lp-header__nav a { font-size: 0.6875rem; }
    }
    .lp-f1 .lp-header__nav a:hover {
        opacity: 0.65;
    }
    .lp-f1 .lp-header-v2 .lp-login-btn--header {
        margin-left: auto;
        background: var(--lp-indigo);
        color: var(--lp-ink);
        border: 2px solid var(--lp-ink);
        transition: background 0.2s ease;
    }
    .lp-f1 .lp-header-v2 .lp-login-btn--header:hover {
        background: var(--lp-indigo-hover);
        color: var(--lp-ink);
    }

    .lp-f1 #about,
    .lp-f1 #support,
    .lp-f1 #tarify {
        scroll-margin-top: 0.75rem;
    }
</style>

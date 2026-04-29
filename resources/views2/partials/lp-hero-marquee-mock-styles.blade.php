<style>
    /* Переменные макета задаются на .lp-container в lp-header-views2-styles */
    .lp-f1 section.hero {
        display: flex;
        flex-direction: column;
        min-height: 500px;
        border-bottom: var(--mock-border);
        background: transparent;
    }

    .lp-f1 .hero-content {
        padding: 30px 20px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        flex: 1;
    }

    @media (min-width: 768px) {
        .lp-f1 .hero-content {
            padding: 60px;
        }
    }

    .lp-f1 .trust-badge {
        display: inline-block;
        background: var(--mock-accent);
        color: var(--mock-dark);
        padding: 8px 16px;
        font-weight: 800;
        font-size: 12px;
        text-transform: uppercase;
        margin-bottom: 20px;
        border: var(--mock-border);
        width: fit-content;
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
    }

    @media (min-width: 768px) {
        .lp-f1 .trust-badge {
            font-size: 14px;
            padding: 10px 20px;
        }
    }

    .lp-f1 .hero-title {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 48px;
        line-height: 1;
        font-weight: 800;
        margin-bottom: 20px;
        text-transform: uppercase;
        color: var(--mock-dark);
    }

    /* как .hero-title span в макете */
    .lp-f1 .hero-title-em {
        font-family: "Playfair Display", Georgia, serif;
        font-style: italic;
        font-weight: 700;
        color: var(--mock-primary);
        text-transform: uppercase;
    }

    @media (min-width: 768px) {
        .lp-f1 .hero-title {
            font-size: 70px;
            margin-bottom: 30px;
        }
    }

    @media (min-width: 1024px) {
        .lp-f1 .hero-title {
            font-size: 90px;
        }
    }

    .lp-f1 .hero-description {
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 16px;
        line-height: 1.6;
        margin-bottom: 30px;
        color: #555;
    }

    @media (min-width: 768px) {
        .lp-f1 .hero-description {
            font-size: 18px;
            margin-bottom: 40px;
        }
    }

    .lp-f1 .hero-buttons {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    @media (min-width: 640px) {
        .lp-f1 .hero-buttons {
            flex-direction: row;
            gap: 20px;
        }
    }

    .lp-f1 .btn-cta {
        background: var(--mock-accent);
        padding: 10px 16px;
        border: var(--mock-border);
        box-shadow: 4px 4px 0px var(--mock-dark);
        font-weight: 800;
        text-transform: uppercase;
        transition: 0.2s;
        font-size: 12px;
        cursor: pointer;
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: var(--mock-dark);
    }

    @media (min-width: 768px) {
        .lp-f1 .btn-cta {
            padding: 12px 24px;
            font-size: 14px;
        }
    }

    .lp-f1 .btn-cta:hover {
        transform: translate(-2px, -2px);
        box-shadow: 6px 6px 0px var(--mock-dark);
    }

    @media (prefers-reduced-motion: reduce) {
        .lp-f1 .btn-cta:hover {
            transform: none;
        }
    }

    .lp-f1 .btn-cta.btn-cta--primary {
        background: var(--mock-primary);
        color: #fff;
    }

    .lp-f1 .hero-note {
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        font-size: 14px;
        color: #888;
        margin-top: 15px;
    }

    .lp-f1 .marquee {
        background: #1a1a1a;
        color: #fff;
        padding: 15px 0;
        overflow: hidden;
        border-bottom: 3px solid #1a1a1a;
    }

    @media (min-width: 768px) {
        .lp-f1 .marquee {
            padding: 20px 0;
        }
    }

    /* два одинаковых сегмента в ряд — translate -50% совпадает с макетом */
    .lp-f1 .marquee-track {
        display: flex;
        width: max-content;
        animation: lp-mock-marquee-scroll 20s linear infinite;
        white-space: nowrap;
    }

    .lp-f1 .marquee-segment {
        flex-shrink: 0;
        font-size: 16px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 3px;
        font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif;
        padding-right: 2rem;
        color: #fff;
    }

    @media (min-width: 768px) {
        .lp-f1 .marquee-segment {
            font-size: 24px;
            letter-spacing: 5px;
        }
    }

    @keyframes lp-mock-marquee-scroll {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }

    @media (prefers-reduced-motion: reduce) {
        .lp-f1 .marquee-track {
            animation: none;
        }
    }
</style>

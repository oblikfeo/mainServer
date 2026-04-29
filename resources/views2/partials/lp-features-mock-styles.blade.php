<style>
    /* Блок #features как в nadezhda.html; карточки — выравнивание по центру */
    .lp-f1 .lp-features-section.section-padding {
        padding: 40px 20px;
        border-bottom: var(--mock-border);
        background: transparent;
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-features-section.section-padding {
            padding: 60px 40px;
        }
    }

    @media (min-width: 1024px) {
        .lp-f1 .lp-features-section.section-padding {
            padding: 80px 50px;
        }
    }

    .lp-f1 .lp-features-section .section-header {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-bottom: 40px;
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-features-section .section-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: flex-end;
            gap: 0;
            margin-bottom: 60px;
        }
    }

    .lp-f1 .lp-features-section .section-title {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 36px;
        text-transform: uppercase;
        line-height: 1;
        font-weight: 800;
        color: var(--mock-dark);
        margin: 0;
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-features-section .section-title {
            font-size: 48px;
        }
    }

    @media (min-width: 1024px) {
        .lp-f1 .lp-features-section .section-title {
            font-size: 64px;
        }
    }

    .lp-f1 .lp-features-section .about-text {
        font-size: 16px;
        line-height: 1.8;
        color: #555;
        max-width: 800px;
        margin: 0 0 50px;
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-features-section .about-text {
            font-size: 18px;
            margin-bottom: 60px;
        }
    }

    .lp-f1 .lp-features-section .features-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-features-section .features-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }
    }

    .lp-f1 .lp-features-section .feature-card {
        background: #fff;
        border: var(--mock-border);
        padding: 30px;
        transition: transform 0.3s, box-shadow 0.3s;
        text-align: center;
    }

    .lp-f1 .lp-features-section .feature-card:hover {
        box-shadow: var(--mock-shadow);
        transform: translate(-5px, -5px);
    }

    .lp-f1 .lp-features-section .feature-icon {
        font-size: 40px;
        margin-bottom: 20px;
        line-height: 1;
    }

    .lp-f1 .lp-features-section .feature-card h3 {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 20px;
        font-weight: 800;
        margin: 0 0 15px;
        text-transform: uppercase;
        color: var(--mock-dark);
    }

    .lp-f1 .lp-features-section .feature-card p {
        font-size: 14px;
        color: #666;
        line-height: 1.6;
        margin: 0;
    }

    @media (min-width: 768px) {
        .lp-f1 .lp-features-section .feature-card {
            padding: 40px;
        }

        .lp-f1 .lp-features-section .feature-card h3 {
            font-size: 24px;
        }

        .lp-f1 .lp-features-section .feature-card p {
            font-size: 16px;
        }
    }
</style>

<style>
    /* Шаги инструкции длиннее, чем в /tutorial: разрешаем прокрутку внутри сцены. */
    .lp-f1 .lp-tutorial__stage { overflow-y: auto; -webkit-overflow-scrolling: touch; }
    .lp-f1 .lp-tutorial__slide,
    .lp-f1 .lp-tutorial__intro { flex: none; }
    .lp-f1 .lp-tutorial__slide .lp-tutorial__text { flex: none; }

    /* Путь по меню телефона: Настройки → Ваше имя */
    .lp-f1 .as-path {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.4rem;
        margin-top: 1rem;
        padding: 0.75rem 0.85rem;
        border: 3px solid var(--lp-ink);
        background: #fff;
        box-shadow: 4px 4px 0 var(--lp-ink);
    }

    .lp-f1 .as-path__item {
        background: var(--lp-ink);
        color: #fff;
        font-size: 0.8125rem;
        font-weight: 800;
        padding: 0.3rem 0.55rem;
    }

    .lp-f1 .as-path__arrow {
        font-size: 1rem;
        font-weight: 900;
        color: var(--lp-orange);
    }

    /* Плашка-предупреждение */
    .lp-f1 .as-note {
        margin-top: 1rem;
        padding: 0.85rem 0.9rem;
        border: 3px solid var(--lp-ink);
        font-size: 0.8438rem;
        font-weight: 600;
        line-height: 1.5;
        text-align: left;
    }

    .lp-f1 .as-note--warn {
        background: #fff3cd;
        box-shadow: 4px 4px 0 var(--lp-ink);
    }

    /* Выбор страны */
    .lp-f1 .as-choice {
        margin-top: 1rem;
        border: 3px solid var(--lp-ink);
        background: #fff;
        box-shadow: 4px 4px 0 var(--lp-ink);
    }

    .lp-f1 .as-choice__row {
        display: flex;
        align-items: flex-start;
        gap: 0.7rem;
        padding: 0.8rem 0.85rem;
    }

    .lp-f1 .as-choice__row + .as-choice__row {
        border-top: 3px solid var(--lp-ink);
    }

    .lp-f1 .as-choice__flag {
        font-size: 1.5rem;
        line-height: 1.1;
        flex-shrink: 0;
    }

    .lp-f1 .as-choice__name {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 0.9375rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: -0.01em;
        color: var(--lp-ink);
    }

    .lp-f1 .as-choice__desc {
        margin-top: 0.15rem;
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1.4;
        color: #444;
    }

    /* Готовые данные для заполнения полей */
    .lp-f1 .as-fill {
        margin-top: 1rem;
        border: 3px solid var(--lp-ink);
        background: #e8f4f8;
        box-shadow: 4px 4px 0 var(--lp-ink);
    }

    .lp-f1 .as-fill__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.6rem 0.85rem;
    }

    .lp-f1 .as-fill__row + .as-fill__row {
        border-top: 2px solid var(--lp-ink);
    }

    .lp-f1 .as-fill__k {
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #555;
    }

    .lp-f1 .as-fill__v {
        font-size: 0.9375rem;
        font-weight: 800;
        color: var(--lp-ink);
        text-align: right;
        overflow-wrap: anywhere;
    }

    /* Финал: вопросы-ответы */
    .lp-f1 .as-done { justify-content: flex-start; padding-top: 1.75rem; }

    .lp-f1 .as-done__text {
        flex: none;
        text-align: center;
        max-width: 24rem;
    }

    .lp-f1 .as-faq {
        margin-top: 1.5rem;
        width: 100%;
        text-align: left;
        border: 3px solid var(--lp-ink);
        background: #fff;
        box-shadow: 4px 4px 0 var(--lp-ink);
    }

    .lp-f1 .as-faq__item { padding: 0.85rem 0.9rem; }

    .lp-f1 .as-faq__item + .as-faq__item {
        border-top: 3px solid var(--lp-ink);
    }

    .lp-f1 .as-faq__q {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 0.875rem;
        font-weight: 800;
        line-height: 1.3;
        color: var(--lp-ink);
    }

    .lp-f1 .as-faq__a {
        margin-top: 0.35rem;
        font-size: 0.8438rem;
        font-weight: 500;
        line-height: 1.5;
        color: #444;
    }
</style>

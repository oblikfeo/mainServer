<style>
    /* ---------- Ширина ----------
       Базовый .lp-container--tutorial из tutorial-styles ограничен 480px.
       Переопределяем только для этой страницы, /tutorial не трогаем. */
    .lp-f1 .lp-container--applestore { max-width: 720px; }

    @media (min-width: 768px) {
        .lp-f1 .lp-container--applestore { max-width: 720px; }
    }

    /* ---------- Сцена ----------
       Все слайды лежат в ОДНОЙ ячейке grid друг над другом. Высота сцены =
       высота самого большого слайда, поэтому при переключении ничего не
       схлопывается: нет ни мигания, ни прыжка кнопок внизу. */
    .lp-f1 .as-stage {
        flex: 1;
        display: grid;
        min-height: 0;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .lp-f1 .as-pane {
        grid-area: 1 / 1;              /* все панели в одной клетке */
        display: flex;
        flex-direction: column;
        min-width: 0;
        transition: opacity .28s ease;
    }

    .lp-f1 .as-pane--on {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .lp-f1 .as-pane--off {
        opacity: 0;
        visibility: hidden;            /* убирает с фокуса и из чтения скринридером */
        pointer-events: none;
    }

    /* ---------- Наполнение слайда ----------
       Центрируем по вертикали: короткий шаг не оставляет пустоту снизу,
       длинный — просто прокручивается. */
    .lp-f1 .as-pane__inner {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 1.75rem 1.25rem;
        max-width: 34rem;
        width: 100%;
        margin: 0 auto;
    }

    @media (min-width: 768px) {
        .lp-f1 .as-pane__inner { padding: 2.25rem 2rem; }
    }

    .lp-f1 .as-pane__inner--center { text-align: center; align-items: center; }

    /* Внутри наших панелей текст не растягивается: flex:1 из tutorial-styles
       раздвигал абзацы и создавал дыры. */
    .lp-f1 .as-pane .lp-tutorial__text,
    .lp-f1 .as-pane .lp-tutorial__title,
    .lp-f1 .as-pane .lp-tutorial__icon { flex: none; }

    .lp-f1 .as-pane--on .lp-tutorial__icon { margin-bottom: 1.1rem; }

    /* ---------- Интро ---------- */
    .lp-f1 .as-badge {
        display: inline-block;
        align-self: center;
        background: var(--lp-ink);
        color: #fff;
        font-size: .625rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .06em;
        padding: .35rem .65rem;
        margin-bottom: 1.25rem;
    }

    .lp-f1 .as-intro-title {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: 2rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: -.03em;
        line-height: 1.1;
        margin: 0 0 1rem;
        color: var(--lp-ink);
    }

    @media (min-width: 768px) {
        .lp-f1 .as-intro-title { font-size: 2.5rem; }
    }

    .lp-f1 .as-intro-text {
        font-size: 1rem;
        font-weight: 500;
        line-height: 1.55;
        color: #444;
        margin: 0;
        max-width: 30rem;
    }

    /* ---------- Путь по меню телефона ---------- */
    .lp-f1 .as-path {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .4rem;
        margin-top: 1rem;
        padding: .75rem .85rem;
        border: 3px solid var(--lp-ink);
        background: #fff;
        box-shadow: 4px 4px 0 var(--lp-ink);
    }

    .lp-f1 .as-path__item {
        background: var(--lp-ink);
        color: #fff;
        font-size: .8125rem;
        font-weight: 800;
        padding: .3rem .55rem;
    }

    .lp-f1 .as-path__arrow { font-size: 1rem; font-weight: 900; color: var(--lp-orange); }

    /* ---------- Плашка-предупреждение ---------- */
    .lp-f1 .as-note {
        margin-top: 1.25rem;
        padding: .85rem .9rem;
        border: 3px solid var(--lp-ink);
        font-size: .8438rem;
        font-weight: 600;
        line-height: 1.5;
        text-align: left;
        max-width: 30rem;
    }

    .lp-f1 .as-note--warn { background: #fff3cd; box-shadow: 4px 4px 0 var(--lp-ink); }

    /* ---------- Выбор страны ---------- */
    .lp-f1 .as-choice {
        margin-top: 1rem;
        border: 3px solid var(--lp-ink);
        background: #fff;
        box-shadow: 4px 4px 0 var(--lp-ink);
    }

    .lp-f1 .as-choice__row { display: flex; align-items: flex-start; gap: .7rem; padding: .85rem .9rem; }
    .lp-f1 .as-choice__row + .as-choice__row { border-top: 3px solid var(--lp-ink); }
    .lp-f1 .as-choice__flag { font-size: 1.5rem; line-height: 1.1; flex-shrink: 0; }

    .lp-f1 .as-choice__name {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: .9375rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: -.01em;
        color: var(--lp-ink);
    }

    .lp-f1 .as-choice__desc {
        margin-top: .15rem;
        font-size: .8125rem;
        font-weight: 500;
        line-height: 1.4;
        color: #444;
    }

    /* ---------- Спрятанная подготовка ---------- */
    .lp-f1 .as-trouble { margin-top: 1.25rem; }

    .lp-f1 .as-trouble__toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        width: 100%;
        padding: .8rem .9rem;
        border: 3px solid var(--lp-ink);
        background: #fff;
        font-family: inherit;
        font-size: .8125rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: var(--lp-ink);
        cursor: pointer;
        text-align: left;
        -webkit-tap-highlight-color: transparent;
    }

    .lp-f1 .as-trouble__toggle:hover { background: #f5f5f5; }

    .lp-f1 .as-trouble__chevron {
        font-size: 1rem;
        color: var(--lp-orange);
        transition: transform .2s ease;
        flex-shrink: 0;
    }

    .lp-f1 .as-trouble__chevron--open { transform: rotate(180deg); }

    .lp-f1 .as-trouble__body {
        border: 3px solid var(--lp-ink);
        border-top: none;
        background: #fff8e6;
        padding: .9rem;
    }

    .lp-f1 .as-trouble__lead {
        margin: 0 0 .85rem;
        font-size: .8438rem;
        font-weight: 600;
        line-height: 1.45;
        color: #333;
    }

    .lp-f1 .as-fix { display: flex; align-items: flex-start; gap: .7rem; }
    .lp-f1 .as-fix + .as-fix { margin-top: .9rem; padding-top: .9rem; border-top: 2px solid var(--lp-ink); }

    .lp-f1 .as-fix__num {
        width: 1.6rem;
        height: 1.6rem;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--lp-ink);
        color: #fff;
        font-size: .8125rem;
        font-weight: 900;
    }

    .lp-f1 .as-fix__title {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: .875rem;
        font-weight: 800;
        color: var(--lp-ink);
    }

    .lp-f1 .as-fix__text {
        margin-top: .25rem;
        font-size: .8438rem;
        font-weight: 500;
        line-height: 1.5;
        color: #444;
    }

    /* ---------- Готовые данные для полей ---------- */
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
        gap: .75rem;
        padding: .6rem .85rem;
    }

    .lp-f1 .as-fill__row + .as-fill__row { border-top: 2px solid var(--lp-ink); }

    .lp-f1 .as-fill__k {
        font-size: .75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #555;
    }

    .lp-f1 .as-fill__v {
        font-size: .9375rem;
        font-weight: 800;
        color: var(--lp-ink);
        text-align: right;
        overflow-wrap: anywhere;
    }

    /* ---------- Финал ---------- */
    .lp-f1 .as-done-head { display: flex; flex-direction: column; align-items: center; }

    .lp-f1 .as-done__text {
        font-size: .9375rem;
        font-weight: 500;
        line-height: 1.55;
        color: #333;
        margin: 0;
        text-align: center;
        max-width: 26rem;
    }

    .lp-f1 .as-faq {
        margin-top: 1.5rem;
        width: 100%;
        text-align: left;
        border: 3px solid var(--lp-ink);
        background: #fff;
        box-shadow: 4px 4px 0 var(--lp-ink);
    }

    .lp-f1 .as-faq__item { padding: .85rem .9rem; }
    .lp-f1 .as-faq__item + .as-faq__item { border-top: 3px solid var(--lp-ink); }

    .lp-f1 .as-faq__q {
        font-family: "Syne", ui-sans-serif, system-ui, sans-serif;
        font-size: .875rem;
        font-weight: 800;
        line-height: 1.3;
        color: var(--lp-ink);
    }

    .lp-f1 .as-faq__a {
        margin-top: .35rem;
        font-size: .8438rem;
        font-weight: 500;
        line-height: 1.5;
        color: #444;
    }

    /* ---------- Кнопки: на широком экране в ряд ---------- */
    @media (min-width: 768px) {
        .lp-f1 .lp-container--applestore .lp-tutorial__actions {
            flex-direction: row-reverse;
            justify-content: center;
            gap: .75rem;
            padding: 1.15rem 2rem max(1.25rem, env(safe-area-inset-bottom));
        }

        .lp-f1 .lp-container--applestore .lp-tutorial__actions > * { max-width: 16rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .lp-f1 .as-pane { transition: none; }
        .lp-f1 .as-trouble__chevron { transition: none; }
    }
</style>

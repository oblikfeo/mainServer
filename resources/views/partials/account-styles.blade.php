{{-- Стили страниц «Моя подписка», «Устройства», «Оплата» (схема «аккаунт = подписка») --}}
<style>
    .lp-f1 .acc { --acc-ink: var(--lp-ink, #111); --acc-orange: var(--lp-orange, #FF4500); --acc-paper: #fffef5; --acc-muted: #5b6470; --acc-ok: #0a7a3d; }
    .lp-f1 .acc * { box-sizing: border-box; }
    .lp-f1 .acc [hidden] { display: none !important; }

    .lp-f1 .acc-block { border: 4px solid var(--acc-ink); background: #fff; box-shadow: 8px 8px 0 var(--acc-ink); padding: 1rem; margin-bottom: 1.5rem; }
    @media (min-width: 640px) { .lp-f1 .acc-block { padding: 1.35rem 1.5rem 1.5rem; } }
    .lp-f1 .acc-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.9rem; }
    .lp-f1 .acc-chip { display: inline-block; background: var(--acc-ink); color: #fff; padding: 0.3rem 0.6rem; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; }
    .lp-f1 .acc-chip--orange { background: var(--acc-orange); }
    .lp-f1 .acc-chip--ghost { background: #fff; color: var(--acc-ink); border: 2px solid var(--acc-ink); padding: 0.2rem 0.5rem; }
    .lp-f1 .acc-hint { font-size: 0.8125rem; font-weight: 600; color: var(--acc-muted); line-height: 1.4; }

    /* Статус подписки */
    .lp-f1 .acc-status { display: flex; flex-wrap: wrap; align-items: center; gap: 0.6rem 0.9rem; }
    .lp-f1 .acc-pill { border: 3px solid var(--acc-ink); padding: 0.25rem 0.7rem; font-size: 0.8125rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }
    .lp-f1 .acc-pill--on { background: var(--acc-orange); color: #fff; }
    .lp-f1 .acc-pill--trial { background: var(--acc-ink); color: #fff; }
    .lp-f1 .acc-pill--off { background: #fff; color: var(--acc-muted); }
    .lp-f1 .acc-status__until { font-size: 1rem; font-weight: 800; }
    .lp-f1 .acc-status__left { font-size: 0.875rem; font-weight: 600; color: var(--acc-muted); }
    .lp-f1 .acc-facts { display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; margin-top: 1rem; }
    @media (min-width: 640px) { .lp-f1 .acc-facts { grid-template-columns: repeat(3, 1fr); } }
    .lp-f1 .acc-fact { border: 3px solid var(--acc-ink); background: var(--acc-paper); padding: 0.6rem 0.7rem; min-width: 0; }
    .lp-f1 .acc-fact__k { font-size: 0.6875rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; color: var(--acc-muted); }
    .lp-f1 .acc-fact__v { margin-top: 0.15rem; font-size: 1.05rem; font-weight: 900; }
    .lp-f1 .acc-actions { display: flex; flex-wrap: wrap; gap: 0.6rem; margin-top: 1rem; }
    .lp-f1 .acc .acc-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.8rem 1.1rem; border: 3px solid var(--acc-ink) !important; background: #fff !important; color: var(--acc-ink) !important; font-family: inherit; font-size: 0.875rem !important; font-weight: 900; text-transform: uppercase; letter-spacing: 0.04em; text-decoration: none; cursor: pointer; box-shadow: 4px 4px 0 var(--acc-ink) !important; }
    .lp-f1 .acc .acc-btn:hover { transform: translate(1px, 1px); box-shadow: 3px 3px 0 var(--acc-ink); }
    .lp-f1 .acc .acc-btn--primary { background: var(--acc-orange) !important; color: #fff !important; }
    .lp-f1 .acc .acc-btn--wide { width: 100%; padding: 1rem; font-size: 1rem; }
    .lp-f1 .acc .acc-btn:disabled { background: #d8d8d8 !important; color: #8a8a8a !important; box-shadow: none !important; cursor: default; transform: none; }
    .lp-f1 .acc-note { border: 3px solid var(--acc-ink); border-left-width: 10px; background: #fff; padding: 0.7rem 0.9rem; font-size: 0.875rem; font-weight: 700; }
    .lp-f1 .acc-note--warn { border-color: var(--acc-orange); }

    /* Карточки выбора (срок, устройства) */
    .lp-f1 .acc-cards { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; }
    @media (min-width: 640px) { .lp-f1 .acc-cards { grid-template-columns: repeat(var(--acc-cols, 3), minmax(0, 1fr)); gap: 1rem; } }
    .lp-f1 .acc-card { position: relative; display: block; width: 100%; text-align: left; min-height: 8.5rem; padding: 0.8rem 0.85rem 0.9rem; border: 4px solid var(--acc-ink); background: #fff; color: var(--acc-ink); font-family: inherit; cursor: pointer; box-shadow: 6px 6px 0 var(--acc-ink); min-width: 0; }
    .lp-f1 .acc-card:hover { transform: translate(1px, 1px); box-shadow: 5px 5px 0 var(--acc-ink); }
    .lp-f1 .acc-card.is-on { background: var(--acc-orange); color: #fff; }
    .lp-f1 .acc-card__radio { position: absolute; top: 0.7rem; right: 0.7rem; width: 1.15rem; height: 1.15rem; border: 3px solid currentColor; border-radius: 50%; background: #fff; }
    .lp-f1 .acc-card.is-on .acc-card__radio { background: radial-gradient(circle, var(--acc-ink) 0 38%, #fff 42%); border-color: var(--acc-ink); }
    .lp-f1 .acc-card__tag { position: absolute; top: -4px; right: -4px; background: var(--acc-ink); color: #fff; padding: 0.15rem 0.45rem; font-size: 0.625rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; }
    .lp-f1 .acc-card__tag + .acc-card__radio { top: 1.6rem; }
    .lp-f1 .acc-card__big { font-size: 2.5rem; line-height: 1; font-weight: 900; letter-spacing: -0.03em; }
    .lp-f1 .acc-card__big small { font-size: 1rem; font-weight: 900; letter-spacing: 0; margin-left: 0.2rem; }
    .lp-f1 .acc-card__label { margin-top: 0.15rem; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; }
    .lp-f1 .acc-card__price { margin-top: 0.9rem; font-size: 1.25rem; font-weight: 900; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .lp-f1 .acc-card__sub { font-size: 0.75rem; font-weight: 700; opacity: 0.8; }
    .lp-f1 .acc-card__badge { display: inline-block; margin-top: 0.45rem; padding: 0.15rem 0.45rem; background: var(--acc-ink); color: #fff; font-size: 0.75rem; font-weight: 900; }
    .lp-f1 .acc-card.is-on .acc-card__badge { background: #fff; color: var(--acc-ink); }
    .lp-f1 .acc-card--dashed { border-style: dashed; background: #fafafa; box-shadow: none; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
    .lp-f1 .acc-card--dashed.is-on { background: var(--acc-orange); border-style: solid; }
    .lp-f1 .acc-card__plus { font-size: 2.75rem; line-height: 1; font-weight: 900; }

    .lp-f1 .acc-stepper { display: inline-grid; grid-template-columns: 2.75rem minmax(5.5rem, auto) 2.75rem; border: 3px solid var(--acc-ink); background: #fff; }
    .lp-f1 .acc-stepper button { border: 0; background: #fff; font-size: 1.4rem; font-weight: 900; cursor: pointer; height: 2.75rem; color: var(--acc-ink); font-family: inherit; }
    .lp-f1 .acc-stepper button:disabled { color: #c4c4c4; cursor: default; }
    .lp-f1 .acc-stepper output { display: flex; align-items: center; justify-content: center; padding: 0 0.6rem; border-left: 3px solid var(--acc-ink); border-right: 3px solid var(--acc-ink); font-size: 1rem; font-weight: 900; white-space: nowrap; }
    .lp-f1 .acc-row { display: flex; flex-wrap: wrap; align-items: center; gap: 0.6rem 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 3px dashed var(--acc-ink); }
    .lp-f1 .acc-row__label { font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; }

    /* Итог */
    .lp-f1 .acc-sum { background: var(--acc-paper); }
    .lp-f1 .acc-sum__line { display: flex; justify-content: space-between; gap: 0.75rem; padding: 0.2rem 0; font-size: 0.9375rem; font-weight: 600; }
    .lp-f1 .acc-sum__line span:last-child { font-weight: 900; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .lp-f1 .acc-sum__total { display: flex; justify-content: space-between; align-items: baseline; gap: 0.75rem; margin-top: 0.6rem; padding-top: 0.6rem; border-top: 4px solid var(--acc-ink); }
    .lp-f1 .acc-sum__total span:first-child { font-size: 0.8125rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em; }
    .lp-f1 .acc-sum__total span:last-child { font-size: 2rem; font-weight: 900; font-variant-numeric: tabular-nums; }
    .lp-f1 .acc-sum__until { text-align: right; font-size: 0.8125rem; font-weight: 700; color: var(--acc-muted); margin-bottom: 0.9rem; }

    /* Слоты устройств */
    .lp-f1 .acc-slots { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; }
    @media (min-width: 640px) { .lp-f1 .acc-slots { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; } }
    @media (min-width: 900px) { .lp-f1 .acc-slots { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .lp-f1 .acc-slot { position: relative; display: flex; flex-direction: column; min-height: 10.5rem; padding: 0.75rem; border: 4px solid var(--acc-ink); background: #fff; box-shadow: 6px 6px 0 var(--acc-ink); min-width: 0; }
    .lp-f1 .acc-slot--empty { background: var(--acc-paper); border-style: dashed; box-shadow: none; }
    .lp-f1 .acc-slot--frozen { background: #eef2f6; color: #64748b; box-shadow: none; }
    .lp-f1 .acc-slot--add { align-items: center; justify-content: center; text-align: center; border-style: dashed; background: #fff; box-shadow: none; text-decoration: none; color: var(--acc-ink); }
    .lp-f1 .acc-slot--add:hover { background: var(--acc-orange); color: #fff; border-style: solid; }
    .lp-f1 .acc-slot__kind { font-size: 0.625rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; color: var(--acc-muted); }
    .lp-f1 .acc-slot__icon { width: 2rem; height: 2rem; margin: 0.5rem 0 0.35rem; flex: 0 0 auto; }
    .lp-f1 .acc-slot__name { font-size: 0.9375rem; font-weight: 900; line-height: 1.2; overflow-wrap: anywhere; }
    .lp-f1 .acc-slot__meta { margin-top: 0.2rem; font-size: 0.75rem; font-weight: 600; color: var(--acc-muted); }
    .lp-f1 .acc-slot__foot { margin-top: auto; padding-top: 0.6rem; }
    .lp-f1 .acc .acc-slot__btn { width: 100%; padding: 0.4rem !important; border: 2px solid var(--acc-ink) !important; background: #fff !important; box-shadow: none !important; font-family: inherit; font-size: 0.6875rem !important; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer; color: var(--acc-ink) !important; }
    .lp-f1 .acc .acc-slot__btn:hover { background: var(--acc-ink) !important; color: #fff !important; }
    .lp-f1 .acc-slot__paid { margin-top: 0.3rem; font-size: 0.6875rem; font-weight: 800; }
    .lp-f1 .acc-slot__plus { font-size: 2.5rem; line-height: 1; font-weight: 900; }
    .lp-f1 .acc-slot__addlabel { margin-top: 0.35rem; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em; }
    /* Главный экран «Моя подписка»: чёрный блок срока, как «Итого» на оплате */
    .lp-f1 .acc-hero { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; padding: 1.25rem 1.1rem 1.35rem; margin-bottom: 1.5rem; background: var(--acc-ink); color: #fff; box-shadow: 8px 8px 0 var(--acc-orange); }
    @media (min-width: 640px) { .lp-f1 .acc-hero { padding: 1.75rem 2rem 1.9rem; } }
    .lp-f1 .acc-hero__text { min-width: 0; }
    .lp-f1 .acc-hero__chips { display: flex; flex-wrap: wrap; align-items: center; gap: 0.6rem; }
    .lp-f1 .acc-hero__chip { display: inline-block; padding: 0.3rem 0.6rem; background: var(--acc-orange); color: #fff; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; }
    .lp-f1 .acc-hero__code { font-size: 0.75rem; font-weight: 900; letter-spacing: 0.1em; color: #9aa3ad; }
    .lp-f1 .acc-hero__title { margin-top: 0.9rem; font-size: 1.9rem; line-height: 1; font-weight: 900; letter-spacing: -0.02em; text-transform: uppercase; }
    @media (min-width: 640px) { .lp-f1 .acc-hero__title { font-size: 3rem; } }
    .lp-f1 .acc-hero__sub { margin-top: 0.6rem; font-size: 0.875rem; font-weight: 700; color: #c9ced4; }
    .lp-f1 .acc-hero__days { flex: 0 0 auto; text-align: right; color: var(--acc-orange); }
    .lp-f1 .acc-hero__days b { display: block; font-size: 3.5rem; line-height: 0.85; font-weight: 900; letter-spacing: -0.04em; font-variant-numeric: tabular-nums; }
    @media (min-width: 640px) { .lp-f1 .acc-hero__days b { font-size: 5.5rem; } }
    .lp-f1 .acc-hero__days span { display: block; margin-top: 0.35rem; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.12em; color: #fff; }
    /* Пробный период — белый чип; скоро закончится — синий, как стикер на главной (оранжевый — только кнопки); не активна — белый с серым */
    .lp-f1 .acc-hero--trial .acc-hero__chip { background: #fff; color: var(--acc-ink); }
    .lp-f1 .acc-hero--soon { background: #2d31fa; box-shadow: 8px 8px 0 var(--acc-ink); }
    .lp-f1 .acc-hero--soon .acc-hero__chip { background: var(--acc-ink); }
    .lp-f1 .acc-hero--soon .acc-hero__days { color: #fff; }
    .lp-f1 .acc-hero--soon .acc-hero__sub, .lp-f1 .acc-hero--soon .acc-hero__code { color: #dfe0ff; }
    .lp-f1 .acc-hero--inactive, .lp-f1 .acc-hero--none { background: #fff; color: var(--acc-ink); border: 4px solid var(--acc-ink); box-shadow: 8px 8px 0 var(--acc-ink); }
    .lp-f1 .acc-hero--inactive .acc-hero__chip, .lp-f1 .acc-hero--none .acc-hero__chip { background: var(--acc-ink); }
    .lp-f1 .acc-hero--inactive .acc-hero__sub, .lp-f1 .acc-hero--none .acc-hero__sub { color: var(--acc-muted); }

    .lp-f1 .acc .acc-cta { margin-bottom: 1.5rem; padding: 1.15rem; font-size: 1.25rem !important; border-width: 4px !important; box-shadow: 8px 8px 0 var(--acc-ink) !important; }
    .lp-f1 .acc .acc-btn--done { background: var(--acc-ink) !important; color: #fff !important; }

    .lp-f1 .acc-tiles { display: grid; grid-template-columns: 1fr; gap: 0 1.5rem; }
    @media (min-width: 768px) { .lp-f1 .acc-tiles { grid-template-columns: 1fr 1fr; } }
    .lp-f1 .acc-tile { display: flex; flex-direction: column; min-width: 0; }
    .lp-f1 .acc-chip-note { font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; color: var(--acc-muted); }
    .lp-f1 .acc-link__url { display: block; padding: 0.8rem; border: 3px solid var(--acc-ink); background: #fff; font-size: 0.8125rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lp-f1 .acc-tile__row { display: flex; flex-wrap: wrap; gap: 0.6rem; margin-top: auto; padding-top: 1rem; }
    .lp-f1 .acc .acc-tile__btn { flex: 1 1 8rem; }
    .lp-f1 .acc-tile > .acc-tile__btn { margin-top: auto; flex: 0 0 auto; }
    .lp-f1 .acc-tile > .acc-link__url { margin-bottom: 1rem; }
    .lp-f1 .acc-count { display: flex; align-items: baseline; gap: 0.5rem; }
    .lp-f1 .acc-count b { font-size: 3rem; line-height: 0.9; font-weight: 900; letter-spacing: -0.03em; }
    .lp-f1 .acc-count span { font-size: 0.875rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; }
    .lp-f1 .acc-devs__dots { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.75rem; }
    .lp-f1 .acc-dot { display: inline-block; width: 1.5rem; height: 1.5rem; border: 3px solid var(--acc-ink); background: #fff; }
    .lp-f1 .acc-dot--on { background: var(--acc-orange); }
    .lp-f1 .acc-dot--frozen { border-style: dashed; }
    .lp-f1 .acc-dot--frozen.acc-dot--on { background: var(--acc-ink); }

    /* Сворачиваемый блок */
    .lp-f1 .acc-fold > summary { list-style: none; display: flex; align-items: center; justify-content: space-between; gap: 1rem; cursor: pointer; }
    .lp-f1 .acc-fold > summary::-webkit-details-marker { display: none; }
    .lp-f1 .acc-fold__icon { position: relative; width: 2rem; height: 2rem; border: 3px solid var(--acc-ink); background: #fff; flex: 0 0 auto; }
    .lp-f1 .acc-fold__icon::before, .lp-f1 .acc-fold__icon::after { content: ""; position: absolute; left: 50%; top: 50%; width: 0.9rem; height: 3px; background: var(--acc-ink); transform: translate(-50%, -50%); }
    .lp-f1 .acc-fold__icon::after { transform: translate(-50%, -50%) rotate(90deg); }
    .lp-f1 .acc-fold[open] .acc-fold__icon::after { display: none; }
    .lp-f1 .acc-fold[open] .acc-fold__icon { background: var(--acc-orange); }
    .lp-f1 .acc-fold__body { margin-top: 1rem; }
    .lp-f1 .acc-slot--max { align-items: center; justify-content: center; text-align: center; border-style: dashed; background: #f4f4f4; box-shadow: none; color: var(--acc-muted); }
    .lp-f1 .acc-card--static { cursor: default; }
    .lp-f1 .acc-card--static:hover { transform: none; box-shadow: 6px 6px 0 var(--acc-ink); }
</style>

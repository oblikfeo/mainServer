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
    .lp-f1 .acc-pill--on { background: #d9f5e3; color: var(--acc-ok); }
    .lp-f1 .acc-pill--trial { background: #fff3c4; }
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
    .lp-f1 .acc-note { border-left: 6px solid var(--acc-ok); background: #e9f7ee; padding: 0.7rem 0.9rem; font-size: 0.875rem; font-weight: 600; }
    .lp-f1 .acc-note--warn { border-left-color: var(--acc-orange); background: #fff1ea; }

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
</style>

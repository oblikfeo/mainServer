<x-cabinet-layout>
    @include('partials.account-styles')

    @php
        /** @var array<string, mixed> $account */
        /** @var array<string, mixed> $payConfig */
        $state = $account['state'];
        $devWord = static fn (int $n): string => ($n % 10 === 1 && $n % 100 !== 11) ? 'устройство' : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? 'устройства' : 'устройств');
    @endphp

    {{-- Оплата в два шага: устройства → срок. Синий — выбор, чёрный — статус и итог, оранжевый — только кнопка оплаты. --}}
    <style>
        .lp-f1 .acc { --pay-blue: #2d31fa; --pay-lime: #bff000; }
        .lp-f1 .pay-title { margin: 0 0 1rem; font-size: 1.5rem; font-weight: 900; text-transform: uppercase; }
        .lp-f1 .pay-status { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem 1rem; margin-bottom: 1.5rem; padding: 0.9rem 1.1rem; background: var(--acc-ink); color: #fff; box-shadow: 8px 8px 0 var(--pay-blue); }
        .lp-f1 .pay-status__l { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 0.9rem; }
        .lp-f1 .pay-status__t { font-size: 0.9375rem; font-weight: 800; }
        .lp-f1 .pay-status__r { font-size: 0.8125rem; font-weight: 700; color: #aeb4ff; }
        .lp-f1 .pay-chip { display: inline-block; padding: 0.25rem 0.55rem; background: #fff; color: var(--acc-ink); font-size: 0.6875rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; }
        .lp-f1 .pay-chip--lime { background: var(--pay-lime); }

        .lp-f1 .pay-step { margin-bottom: 1.5rem; border: 4px solid var(--acc-ink); background: #fff; box-shadow: 8px 8px 0 var(--acc-ink); }
        .lp-f1 .pay-step__head { display: flex; align-items: center; gap: 0.6rem; padding: 0.7rem 1.1rem; background: var(--pay-blue); color: #fff; border-bottom: 4px solid var(--acc-ink); }
        .lp-f1 .pay-step__n { display: inline-flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; background: #fff; color: var(--acc-ink); font-weight: 900; }
        .lp-f1 .pay-step__t { font-size: 1rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; }
        .lp-f1 .pay-step__hint { margin-left: auto; font-size: 0.8125rem; font-weight: 700; color: #dfe0ff; text-align: right; }
        .lp-f1 .pay-step__body { padding: 1.1rem; }
        @media (min-width: 640px) { .lp-f1 .pay-step__body { padding: 1.35rem 1.5rem 1.5rem; } }
        @media (max-width: 639px) { .lp-f1 .pay-step__hint { display: none; } }

        .lp-f1 .acc-pay2 .acc-card { min-height: 7rem; }
        .lp-f1 .acc-pay2 .acc-card.is-on { background: var(--pay-blue); color: #fff; }
        .lp-f1 .acc-pay2 .acc-card.is-on .acc-card__radio { background: radial-gradient(circle, var(--pay-blue) 0 38%, #fff 42%); border-color: #fff; }
        .lp-f1 .acc-pay2 .acc-card__badge,
        .lp-f1 .acc-pay2 .acc-card.is-on .acc-card__badge { background: var(--pay-lime); color: var(--acc-ink); }
        .lp-f1 .acc-pay2 .acc-card__tag--promo { background: var(--pay-lime); color: var(--acc-ink); }
        .lp-f1 .acc-pay2 .acc-card__price s { margin-left: 0.35rem; font-size: 0.8125rem; opacity: 0.7; }
        .lp-f1 .acc-pay2 .acc-card.is-off { opacity: 0.45; }
        .lp-f1 .acc-pay2 .acc-stepper button:not(:disabled):hover { background: var(--pay-blue); color: #fff; }
        @media (max-width: 639px) {
            .lp-f1 .acc-pay2 .acc-card__big { font-size: 2rem; }
            .lp-f1 .acc-pay2 .acc-card__big small { font-size: 0.8125rem; margin-left: 0.15rem; }
        }

        .lp-f1 .pay-sum { margin-bottom: 1.5rem; }
        .lp-f1 .pay-sum__box { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 0.5rem 1rem; padding: 1rem 1.1rem; background: var(--acc-ink); color: #fff; }
        .lp-f1 .pay-sum__k { font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; color: #aeb4ff; }
        .lp-f1 .pay-sum__v { font-size: 2.5rem; line-height: 1; font-weight: 900; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .lp-f1 .pay-sum__what { font-size: 0.875rem; font-weight: 700; text-align: right; }
        .lp-f1 .pay-sum__what b { color: var(--pay-lime); }
        @media (max-width: 639px) { .lp-f1 .pay-sum__what { text-align: left; } }
        .lp-f1 .pay-sum__how { margin: 0.6rem 0 0.9rem; font-size: 0.8125rem; font-weight: 700; color: var(--acc-muted); }
    </style>

    <div class="max-w-4xl mx-auto acc acc-pay2" id="acc-pay" data-config="{{ json_encode($payConfig, JSON_UNESCAPED_UNICODE) }}" data-link="{{ route('cabinet.payment.link') }}" data-add="{{ request('add') === 'device' ? '1' : '0' }}">
        <h1 class="pay-title">Оплата</h1>

        <div class="pay-status">
            <div class="pay-status__l">
                @if ($state === 'trial')
                    <span class="pay-chip">Пробный период</span>
                    <span class="pay-status__t">до {{ $account['expires_at']?->format('d.m.Y') }} · {{ $account['device_limit'] }} {{ $devWord((int) $account['device_limit']) }}</span>
                @elseif ($state === 'active')
                    <span class="pay-chip">Активна</span>
                    <span class="pay-status__t">{{ $account['expires_at'] ? 'до '.$account['expires_at']->format('d.m.Y') : 'без срока' }}@if (! $account['unlimited_devices']) · {{ $account['device_limit'] }} {{ $devWord((int) $account['device_limit']) }}@endif</span>
                @elseif ($state === 'inactive')
                    <span class="pay-chip">Не активна</span>
                    <span class="pay-status__t">{{ $account['expires_at'] ? 'закончилась '.$account['expires_at']->format('d.m.Y') : '' }}</span>
                @else
                    <span class="pay-chip">Подписки пока нет</span>
                @endif
                @if ($payConfig['bonusDays'] > 0)
                    <span class="pay-chip pay-chip--lime">🎁 {{ $payConfig['bonusDays'] === 30 ? '+1 месяц' : '+'.$payConfig['bonusDays'].' дн.' }} в подарок к первой оплате</span>
                @endif
            </div>
            <span class="pay-status__r" id="pay-status-r"></span>
        </div>

        <div class="acc-note" id="pay-noexpiry" hidden>Подписка без срока — продлевать её не нужно. Чтобы добавить устройства, напишите в поддержку.</div>

        <div class="pay-step" id="pay-step-devices">
            <div class="pay-step__head"><span class="pay-step__n">1</span><span class="pay-step__t">Сколько устройств</span><span class="pay-step__hint" id="pay-devices-hint"></span></div>
            <div class="pay-step__body">
                <div class="acc-cards" id="pay-devices" style="--acc-cols: 5;"></div>
                <div class="acc-row">
                    <span class="acc-row__label">Точное число</span>
                    <div class="acc-stepper">
                        <button type="button" id="pay-minus" aria-label="Меньше устройств">−</button>
                        <output id="pay-count"></output>
                        <button type="button" id="pay-plus" aria-label="Больше устройств">+</button>
                    </div>
                    <span class="acc-hint" id="pay-exact-hint"></span>
                </div>
            </div>
        </div>

        <div class="pay-step" id="pay-step-term">
            <div class="pay-step__head"><span class="pay-step__n" id="pay-term-n">2</span><span class="pay-step__t">Срок</span><span class="pay-step__hint" id="pay-term-hint"></span></div>
            <div class="pay-step__body">
                <div class="acc-cards" id="pay-terms"></div>
            </div>
        </div>

        <div class="pay-sum" id="pay-sum">
            <div class="pay-sum__box">
                <div><div class="pay-sum__k">К оплате</div><div class="pay-sum__v" id="pay-total">0 ₽</div></div>
                <div class="pay-sum__what" id="pay-what"></div>
            </div>
            <div class="pay-sum__how" id="pay-how"></div>
            <button type="button" class="acc-btn acc-btn--primary acc-btn--wide" id="pay-btn">Оплатить</button>
        </div>
    </div>

    <script>
        (function () {
            const root = document.getElementById('acc-pay');
            const cfg = JSON.parse(root.dataset.config);
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const $ = (id) => document.getElementById(id);
            const rub = (n) => n.toLocaleString('ru-RU') + ' ₽';
            const plural = (n, a, b, c) => (n % 10 === 1 && n % 100 !== 11) ? a : (n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20)) ? b : c;
            const devWord = (n) => plural(n, 'устройство', 'устройства', 'устройств');
            const fmtDate = (d) => d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });

            const base = cfg.baseDevices;
            const have = cfg.paidExtra;
            const current = cfg.currentDevices;
            const maxDev = cfg.maxDevices;
            const promo = cfg.promo;
            const termByKey = Object.fromEntries(cfg.terms.map((t) => [t.key, t]));

            // Те же правила, что AccountPricing::quote / promoQuote на сервере.
            const restRub = (count) => (count > 0 && cfg.restDays > 0) ? Math.round(count * cfg.deviceMonthRub * cfg.restDays / cfg.deviceMonthDays) : 0;
            function quote(n, key) {
                const extras = cfg.unlimitedDevices ? 0 : Math.max(0, n - base);
                const fresh = Math.max(0, extras - have);
                const rest = restRub(fresh);
                if (key === '') {
                    return { amount: fresh > 0 ? Math.max(1, rest) : 0, fresh, rest, extras, promo: false };
                }
                const t = termByKey[key];
                const regular = t.amount_rub + cfg.overBase * t.months * cfg.deviceMonthRub + extras * t.months * cfg.deviceMonthRub + rest;
                if (promo && t.months === promo.months && n === promo.devices) {
                    return { amount: promo.amount_rub + rest, regular, fresh, rest, extras, promo: true };
                }
                return { amount: regular, regular, fresh, rest, extras, promo: false };
            }

            // Выбор: n — сколько устройств всего; key — срок ('' — без продления, до конца текущей подписки).
            let n = cfg.unlimitedDevices ? 0 : Math.max(base, current);
            let key = cfg.paidActive ? '' : (cfg.terms[0]?.key || '');
            if (root.dataset.add === '1' && cfg.paidActive && n < maxDev) n += 1;

            function deviceCards() {
                const set = new Set([base, base + 1, base + 2, base + 3]);
                if (current > base) set.add(current);
                if (promo) set.add(promo.devices);
                else if (maxDev > base + 3) set.add(maxDev);
                return [...set].filter((x) => x >= base && x <= maxDev).sort((a, b) => a - b);
            }

            function render() {
                if (cfg.noExpiry) {
                    $('pay-noexpiry').hidden = false;
                    ['pay-step-devices', 'pay-step-term', 'pay-sum'].forEach((id) => { $(id).hidden = true; });
                    return;
                }

                $('pay-status-r').textContent = cfg.paidActive
                    ? 'осталось ' + cfg.restDays + ' ' + plural(cfg.restDays, 'день', 'дня', 'дней')
                    : (cfg.state === 'trial' ? 'после оплаты — ' + base + ' ' + devWord(base) : '');

                // Шаг 1 — устройства
                $('pay-step-devices').hidden = cfg.unlimitedDevices;
                $('pay-term-n').textContent = cfg.unlimitedDevices ? '1' : '2';
                if (!cfg.unlimitedDevices) {
                    $('pay-devices-hint').textContent = cfg.paidActive
                        ? 'новое — ' + rub(cfg.deviceMonthRub) + ' за 30 дней, до конца подписки'
                        : base + ' входят в подписку, каждое следующее +' + rub(cfg.deviceMonthRub) + ' в месяц';
                    let html = '';
                    deviceCards().forEach((x) => {
                        let price, sub, tag = '', off = false;
                        if (cfg.paidActive) {
                            if (x === current) { price = 'есть'; sub = 'подключены сейчас'; }
                            else if (x < current) { price = 'меньше'; sub = 'только при продлении'; off = key === ''; }
                            else { price = '+' + rub(restRub(Math.max(0, x - base - have))); sub = 'до ' + cfg.expiresAt; }
                        } else if (x === base) { price = 'входят'; sub = 'в любой срок'; }
                        else if (promo && x === promo.devices) { price = rub(promo.amount_rub); sub = 'акция: месяц с подпиской'; }
                        else { price = '+' + rub((x - base) * cfg.deviceMonthRub); sub = 'в месяц'; }
                        if (x === current && (cfg.state === 'active' || cfg.state === 'inactive')) tag = '<span class="acc-card__tag">Сейчас</span>';
                        else if (promo && x === promo.devices && !cfg.paidActive) tag = '<span class="acc-card__tag acc-card__tag--promo">Акция</span>';
                        html += '<button type="button" class="acc-card' + (n === x ? ' is-on' : '') + (off ? ' is-off' : '') + '" data-n="' + x + '">' + tag
                            + '<span class="acc-card__radio"></span><div class="acc-card__big">' + x + '</div><div class="acc-card__label">' + devWord(x) + '</div>'
                            + '<div class="acc-card__price">' + price + '</div><div class="acc-card__sub">' + sub + '</div></button>';
                    });
                    $('pay-devices').innerHTML = html;
                    $('pay-count').textContent = n + ' ' + devWord(n);
                    $('pay-minus').disabled = n <= base;
                    $('pay-plus').disabled = n >= maxDev;
                    $('pay-exact-hint').textContent = 'до ' + maxDev + ' на одной подписке';
                }

                // Шаг 2 — срок
                let th = '';
                if (cfg.paidActive) {
                    const q = quote(n, '');
                    th += termCard({ key: '', on: key === '', big: cfg.restDays + '<small>' + plural(cfg.restDays, 'день', 'дня', 'дней') + '</small>', label: 'Без продления',
                        price: q.fresh > 0 ? rub(q.amount) : '—', sub: 'до ' + cfg.expiresAt });
                }
                cfg.terms.forEach((t) => {
                    const q = quote(n, t.key);
                    th += termCard({ key: t.key, on: key === t.key,
                        big: (cfg.paidActive ? '+' : '') + t.months + '<small>' + plural(t.months, 'месяц', 'месяца', 'месяцев') + '</small>',
                        label: cfg.paidActive ? 'Продлить' : '',
                        price: rub(q.amount) + (q.promo ? '<s>' + rub(q.regular) + '</s>' : ''),
                        sub: q.promo ? 'акция' : (t.months > 1 ? '≈ ' + rub(Math.round(q.amount / t.months)) + ' в месяц' : 'за ' + (cfg.unlimitedDevices ? 'все устройства' : n + ' ' + devWord(n))),
                        badge: q.promo ? 'Выгода ' + rub(q.regular - q.amount) : (t.discount_percent > 0 ? 'Скидка −' + t.discount_percent + '%' : ''),
                    });
                });
                $('pay-terms').innerHTML = th;
                $('pay-terms').style.setProperty('--acc-cols', cfg.terms.length + (cfg.paidActive ? 1 : 0));
                $('pay-term-hint').textContent = cfg.paidActive ? 'доп. устройства продлеваются вместе с подпиской' : '';

                // Итог
                const q = quote(n, key);
                const t = termByKey[key] || null;
                let what, how;
                const devs = cfg.unlimitedDevices ? '' : '<b>' + n + ' ' + devWord(n) + '</b> · ';
                if (t) {
                    const from = cfg.active && cfg.expiresAtIso ? new Date(cfg.expiresAtIso) : new Date();
                    from.setDate(from.getDate() + t.days + (cfg.bonusDays > 0 ? cfg.bonusDays : 0));
                    what = devs + 'подписка до ' + fmtDate(from);
                    const parts = [q.promo ? 'акция ' + rub(promo.amount_rub) : 'подписка ' + rub(t.amount_rub)];
                    if (!q.promo && cfg.overBase > 0) parts.push('устройства сверх ' + (base - cfg.overBase) + ': ' + rub(cfg.overBase * t.months * cfg.deviceMonthRub));
                    if (!q.promo && q.extras > 0) parts.push(q.extras + ' доп. ' + devWord(q.extras) + ' на ' + t.months + ' мес.: ' + rub(q.extras * t.months * cfg.deviceMonthRub));
                    if (q.rest > 0) parts.push(q.fresh + ' ' + plural(q.fresh, 'новое', 'новых', 'новых') + ' за оставшиеся ' + cfg.restDays + ' дн.: ' + rub(q.rest));
                    if (cfg.bonusDays > 0) parts.push('🎁 +' + cfg.bonusDays + ' дн. в подарок');
                    how = 'Из чего: ' + parts.join(' + ');
                } else if (q.fresh > 0) {
                    what = devs + 'до ' + cfg.expiresAt;
                    how = q.fresh + ' ' + plural(q.fresh, 'новое устройство', 'новых устройства', 'новых устройств') + ' × ' + cfg.restDays + ' дн. по ' + rub(cfg.deviceMonthRub) + ' за 30 дней';
                } else {
                    what = 'Срок подписки не меняется: до ' + cfg.expiresAt;
                    how = 'Добавьте устройства в шаге 1 или выберите продление.';
                }
                $('pay-total').textContent = rub(q.amount);
                $('pay-what').innerHTML = what;
                $('pay-how').textContent = how;
                const btn = $('pay-btn');
                btn.disabled = q.amount === 0;
                btn.textContent = q.amount === 0 ? 'Выберите, что оплатить' : 'Оплатить ' + rub(q.amount);
            }

            function termCard(o) {
                return '<button type="button" class="acc-card' + (o.on ? ' is-on' : '') + '" data-key="' + o.key + '">'
                    + '<span class="acc-card__radio"></span>'
                    + '<div class="acc-card__big">' + o.big + '</div>'
                    + (o.label ? '<div class="acc-card__label">' + o.label + '</div>' : '')
                    + '<div class="acc-card__price">' + o.price + '</div>'
                    + '<div class="acc-card__sub">' + (o.sub || '') + '</div>'
                    + (o.badge ? '<span class="acc-card__badge">' + o.badge + '</span>' : '')
                    + '</button>';
            }

            // Меньше устройств, чем сейчас, — только вместе с продлением.
            const fewerNeedsRenewal = () => { if (cfg.paidActive && n < current && key === '') key = cfg.terms[0]?.key || ''; };
            $('pay-devices').addEventListener('click', (e) => {
                const b = e.target.closest('[data-n]');
                if (!b) return;
                n = parseInt(b.getAttribute('data-n'), 10);
                fewerNeedsRenewal();
                render();
            });
            $('pay-minus').addEventListener('click', () => { n = Math.max(base, n - 1); fewerNeedsRenewal(); render(); });
            $('pay-plus').addEventListener('click', () => { n = Math.min(maxDev, n + 1); render(); });
            $('pay-terms').addEventListener('click', (e) => {
                const b = e.target.closest('[data-key]');
                if (!b) return;
                key = b.getAttribute('data-key');
                if (key === '' && n < current) n = current;
                render();
            });

            $('pay-btn').addEventListener('click', () => {
                const q = quote(n, key);
                pay($('pay-btn'), { term: q.promo ? 'promo' : (key || null), devices: cfg.unlimitedDevices ? 0 : n, device_months: 0 });
            });

            async function pay(btn, body) {
                const old = btn.textContent;
                btn.disabled = true;
                btn.textContent = '…';
                try {
                    const r = await fetch(root.dataset.link, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify(body),
                    });
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || !data.url) throw new Error(data.error || 'failed');
                    window.location.href = data.url;
                } catch (err) {
                    btn.disabled = false;
                    btn.textContent = old;
                    const messages = {
                        term_required: 'Подписка не активна — выберите срок.',
                        no_expiry: 'Подписка без срока — напишите в поддержку.',
                        nothing_to_pay: 'Добавьте устройства или выберите продление.',
                        devices_out_of_range: 'Столько устройств на одной подписке подключить нельзя.',
                        payments_not_configured: 'Оплата временно недоступна. Напишите в поддержку.',
                        promo_unavailable: 'Акция для вашей подписки недоступна. Выберите обычный срок.',
                    };
                    alert(messages[String(err.message)] || 'Не удалось создать ссылку оплаты. Попробуйте ещё раз.');
                }
            }

            render();
        })();
    </script>
</x-cabinet-layout>

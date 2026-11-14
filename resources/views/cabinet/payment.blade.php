<x-cabinet-layout>
    @include('partials.account-styles')

    @php
        /** @var array<string, mixed> $account */
        /** @var array<string, mixed> $payConfig */
        $state = $account['state'];
        $devWord = static fn (int $n): string => ($n % 10 === 1 && $n % 100 !== 11) ? 'устройство' : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? 'устройства' : 'устройств');
    @endphp

    {{-- Оплата в два шага. Новая покупка / пробный / закончилась: 1 — срок, 2 — устройства.
         Оплаченная активная: 1 — сколько устройств добавить, 2 — срок: по умолчанию «N дней до конца подписки», можно продлить. --}}
    <style>
        .lp-f1 .pay-title { margin: 0 0 0.9rem; font-size: 1.5rem; font-weight: 900; text-transform: uppercase; }
        .lp-f1 .pay-now { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 1rem; margin-bottom: 1.25rem; font-size: 0.875rem; font-weight: 700; color: var(--acc-muted); }
        .lp-f1 .pay-step { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.9rem; }
        .lp-f1 .pay-step__n { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; background: var(--acc-ink); color: #fff; font-weight: 900; }
        .lp-f1 .pay-step__t { font-size: 1rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.04em; }
        .lp-f1 .pay-step__hint { margin-left: auto; text-align: right; }
        @media (max-width: 639px) { .lp-f1 .pay-step__hint { display: none; } }
        .lp-f1 .acc-pay2 .acc-card { min-height: 7rem; }
        .lp-f1 .acc-pay2 .acc-card__price s { margin-left: 0.35rem; font-size: 0.8125rem; opacity: 0.7; }
        .lp-f1 .acc-pay2 .acc-card.is-off { opacity: 0.45; }
        @media (max-width: 639px) {
            .lp-f1 .acc-pay2 .acc-card__big { font-size: 2rem; }
            .lp-f1 .acc-pay2 .acc-card__big small { font-size: 0.8125rem; margin-left: 0.15rem; }
        }
        .lp-f1 .pay-how { margin: 0.2rem 0 0.9rem; }
        .lp-f1 .acc-pay2 .acc-sum__total { margin-top: 0; padding-top: 0; border-top: 0; }
        .lp-f1 .acc-pay2 .acc-sum__total span:last-child { white-space: nowrap; }
        @media (max-width: 639px) { .lp-f1 .acc-pay2 .acc-sum__total span:last-child { font-size: 1.75rem; } }
        /* Главная карточка срока активной подписки — «N дней до конца подписки», на две колонки */
        .lp-f1 .acc-pay2 .pay-days { grid-column: span 2; }
        .lp-f1 .acc-pay2 .pay-days .acc-card__big { font-size: 3.5rem; }
        .lp-f1 .acc-pay2 .pay-days .acc-card__big small { font-size: 1.1rem; }
        @media (max-width: 639px) { .lp-f1 .acc-pay2 .pay-days .acc-card__big { font-size: 2.75rem; } }
    </style>

    <div class="max-w-4xl mx-auto acc acc-pay2" id="acc-pay" data-config="{{ json_encode($payConfig, JSON_UNESCAPED_UNICODE) }}" data-link="{{ route('cabinet.payment.link') }}" data-add="{{ request('add') === 'device' ? '1' : '0' }}">
        <h1 class="pay-title">Оплата</h1>

        <div class="pay-now">
            @if ($state === 'trial')
                <span class="acc-pill acc-pill--trial">Пробный период</span>
                <span>до {{ $account['expires_at']?->format('d.m.Y') }} · {{ $account['device_limit'] }} {{ $devWord((int) $account['device_limit']) }}</span>
            @elseif ($state === 'active')
                <span class="acc-pill acc-pill--on">Активна</span>
                <span>{{ $account['expires_at'] ? 'до '.$account['expires_at']->format('d.m.Y') : 'без срока' }}@if (! $account['unlimited_devices']) · {{ $account['device_limit'] }} {{ $devWord((int) $account['device_limit']) }}@endif</span>
            @elseif ($state === 'inactive')
                <span class="acc-pill acc-pill--off">Не активна</span>
                <span>{{ $account['expires_at'] ? 'закончилась '.$account['expires_at']->format('d.m.Y') : '' }}</span>
            @else
                <span class="acc-pill acc-pill--off">Подписки пока нет</span>
            @endif
            <span id="pay-now-r"></span>
            @if ($payConfig['bonusDays'] > 0)
                <span class="acc-chip acc-chip--orange">🎁 {{ $payConfig['bonusDays'] === 30 ? '+1 месяц' : '+'.$payConfig['bonusDays'].' дн.' }} в подарок к первой оплате</span>
            @endif
        </div>

        <div class="acc-note" id="pay-noexpiry" hidden>Подписка без срока — продлевать её не нужно. Чтобы добавить устройства, напишите в поддержку.</div>

        <div class="acc-block" id="pay-step-term">
            <div class="pay-step"><span class="pay-step__n" id="pay-term-n">1</span><span class="pay-step__t">Срок</span><span class="acc-hint pay-step__hint" id="pay-term-hint"></span></div>
            <div class="acc-cards" id="pay-terms"></div>
        </div>

        <div class="acc-block" id="pay-step-devices">
            <div class="pay-step"><span class="pay-step__n" id="pay-dev-n">2</span><span class="pay-step__t" id="pay-dev-t">Сколько устройств</span><span class="acc-hint pay-step__hint" id="pay-devices-hint"></span></div>
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

        <div class="acc-block acc-sum" id="pay-sum">
            <div class="acc-sum__total"><span id="pay-what"></span><span id="pay-total">0 ₽</span></div>
            <div class="acc-sum__until" id="pay-until"></div>
            <div class="acc-hint pay-how" id="pay-how"></div>
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
            const monWord = (n) => plural(n, 'месяц', 'месяца', 'месяцев');
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

            // Выбор: key — срок ('' — без продления, до конца текущей подписки); n — сколько устройств всего.
            // Оплаченная активная подписка: по умолчанию +1 устройство до конца подписки; продлить можно в шаге 2.
            let n = cfg.unlimitedDevices ? 0 : Math.max(base, current);
            let key = cfg.paidActive ? '' : (cfg.terms[0]?.key || '');
            if (cfg.paidActive && n < maxDev) n += 1;
            if (cfg.paidActive) {
                $('pay-step-term').before($('pay-step-devices'));
                $('pay-dev-n').textContent = '1';
                $('pay-term-n').textContent = '2';
                $('pay-dev-t').textContent = 'Сколько устройств';
            }

            function deviceCards() {
                const set = new Set([base, base + 1, base + 2, base + 3]);
                if (current > base) set.add(current);
                if (promo) set.add(promo.devices);
                else if (maxDev > base + 3) set.add(maxDev);
                const min = cfg.paidActive && key === '' ? current : base;
                if (cfg.paidActive) [1, 2, 3].forEach((d) => set.add(current + d));
                return [...set].filter((x) => x >= min && x <= maxDev).sort((a, b) => a - b).slice(0, 5);
            }

            function render() {
                if (cfg.noExpiry) {
                    $('pay-noexpiry').hidden = false;
                    ['pay-step-devices', 'pay-step-term', 'pay-sum'].forEach((id) => { $(id).hidden = true; });
                    return;
                }
                const t = termByKey[key] || null;

                $('pay-now-r').textContent = cfg.paidActive
                    ? '· осталось ' + cfg.restDays + ' ' + plural(cfg.restDays, 'день', 'дня', 'дней')
                    : (cfg.state === 'trial' ? '· после оплаты — ' + base + ' ' + devWord(base) : '');

                // Шаг 1 — срок
                let th = '';
                if (cfg.paidActive) {
                    const q = quote(n, '');
                    th += termCard({ key: '', on: key === '', cls: ' pay-days',
                        big: cfg.restDays + '<small>' + plural(cfg.restDays, 'день', 'дня', 'дней') + '</small>', label: 'до конца подписки · без продления',
                        price: q.fresh > 0 ? rub(q.amount) : '—', sub: 'до ' + cfg.expiresAt });
                }
                cfg.terms.forEach((x) => {
                    const q = quote(n, x.key);
                    th += termCard({ key: x.key, on: key === x.key,
                        big: (cfg.paidActive ? '+' : '') + x.months + '<small>' + monWord(x.months) + '</small>',
                        label: cfg.paidActive ? 'Продлить' : '',
                        price: rub(q.amount) + (q.promo ? '<s>' + rub(q.regular) + '</s>' : ''),
                        sub: q.promo ? 'акция' : (x.months > 1 ? '≈ ' + rub(Math.round(q.amount / x.months)) + ' в месяц' : 'за ' + (cfg.unlimitedDevices ? 'все устройства' : n + ' ' + devWord(n))),
                        badge: q.promo ? 'Выгода ' + rub(q.regular - q.amount) : (x.discount_percent > 0 ? 'Скидка −' + x.discount_percent + '%' : ''),
                    });
                });
                $('pay-terms').innerHTML = th;
                $('pay-terms').style.setProperty('--acc-cols', cfg.terms.length + (cfg.paidActive ? 2 : 0));
                $('pay-term-hint').textContent = cfg.paidActive ? 'или продлите — доп. устройства продлятся вместе с подпиской' : base + ' устройства входят в подписку';

                // Шаг 2 — устройства (доплата считается от выбранного срока)
                $('pay-step-devices').hidden = cfg.unlimitedDevices;
                if (!cfg.unlimitedDevices) {
                    $('pay-devices-hint').textContent = t
                        ? 'каждое следующее +' + rub(cfg.deviceMonthRub) + ' в месяц'
                        : 'новое — ' + rub(cfg.deviceMonthRub) + ' за 30 дней, до конца подписки';
                    const from = key === '' ? current : base;
                    const fromAmount = quote(from, key).amount;
                    let html = '';
                    deviceCards().forEach((x) => {
                        const q = quote(x, key);
                        let price, sub, tag = '', off = false;
                        if (key === '' && x === current) { price = 'есть'; sub = 'подключены сейчас'; off = cfg.paidActive; }
                        else if (key === '' && x < current) { price = 'меньше'; sub = 'только при продлении'; off = true; }
                        else if (x === base) { price = 'входят'; sub = 'в подписку'; }
                        else if (q.promo) { price = rub(q.amount); sub = 'акция: всё вместе'; tag = '<span class="acc-card__tag">Акция</span>'; }
                        else { price = '+' + rub(q.amount - fromAmount); sub = t ? (t.months === 1 ? 'за месяц' : 'за ' + t.months + ' мес.') : 'до ' + cfg.expiresAt; }
                        if (!tag && x === current && (cfg.state === 'active' || cfg.state === 'inactive')) tag = '<span class="acc-card__tag">Сейчас</span>';
                        html += '<button type="button" class="acc-card' + (n === x ? ' is-on' : '') + (off ? ' is-off' : '') + '" data-n="' + x + '">' + tag
                            + '<span class="acc-card__radio"></span><div class="acc-card__big">' + x + '</div><div class="acc-card__label">' + devWord(x) + '</div>'
                            + '<div class="acc-card__price">' + price + '</div><div class="acc-card__sub">' + sub + '</div></button>';
                    });
                    $('pay-devices').innerHTML = html;
                    $('pay-count').textContent = n + ' ' + devWord(n);
                    $('pay-minus').disabled = n <= minN();
                    $('pay-plus').disabled = n >= maxDev;
                    $('pay-exact-hint').textContent = 'до ' + maxDev + ' на одной подписке';
                }

                // Итог
                const q = quote(n, key);
                let what, until, how;
                const devs = cfg.unlimitedDevices ? '' : n + ' ' + devWord(n);
                if (t) {
                    const from = cfg.active && cfg.expiresAtIso ? new Date(cfg.expiresAtIso) : new Date();
                    from.setDate(from.getDate() + t.days + (cfg.bonusDays > 0 ? cfg.bonusDays : 0));
                    what = [devs, (cfg.paidActive ? '+' : '') + t.label].filter(Boolean).join(' · ');
                    until = 'Подписка будет до ' + fmtDate(from);
                    const parts = [q.promo ? 'акция ' + rub(promo.amount_rub) : 'подписка ' + rub(t.amount_rub)];
                    if (!q.promo && cfg.overBase > 0) parts.push('устройства сверх ' + (base - cfg.overBase) + ': ' + rub(cfg.overBase * t.months * cfg.deviceMonthRub));
                    if (!q.promo && q.extras > 0) parts.push(q.extras + ' доп. ' + devWord(q.extras) + ' на ' + t.months + ' мес.: ' + rub(q.extras * t.months * cfg.deviceMonthRub));
                    if (q.rest > 0) parts.push(q.fresh + ' ' + plural(q.fresh, 'новое', 'новых', 'новых') + ' за оставшиеся ' + cfg.restDays + ' дн.: ' + rub(q.rest));
                    if (cfg.bonusDays > 0) parts.push('🎁 +' + cfg.bonusDays + ' дн. в подарок');
                    how = parts.length > 1 ? 'Из чего: ' + parts.join(' + ') : '';
                } else if (q.fresh > 0) {
                    what = devs + ' · до ' + cfg.expiresAt;
                    until = 'Срок подписки не меняется';
                    how = q.fresh + ' ' + plural(q.fresh, 'новое устройство', 'новых устройства', 'новых устройств') + ' × ' + cfg.restDays + ' дн. по ' + rub(cfg.deviceMonthRub) + ' за 30 дней';
                } else {
                    what = current >= maxDev ? 'На подписке уже ' + maxDev + ' ' + devWord(maxDev) + ' — это максимум' : 'Выберите, сколько устройств добавить';
                    until = 'Подписка действует до ' + cfg.expiresAt;
                    how = '';
                }
                $('pay-total').textContent = rub(q.amount);
                $('pay-what').textContent = what;
                $('pay-until').textContent = until;
                $('pay-how').textContent = how;
                $('pay-how').hidden = how === '';
                const btn = $('pay-btn');
                btn.disabled = q.amount === 0;
                btn.textContent = q.amount === 0 ? 'Выберите, что оплатить' : 'Оплатить ' + rub(q.amount);
            }

            function termCard(o) {
                return '<button type="button" class="acc-card' + (o.cls || '') + (o.on ? ' is-on' : '') + '" data-key="' + o.key + '">'
                    + '<span class="acc-card__radio"></span>'
                    + '<div class="acc-card__big">' + o.big + '</div>'
                    + (o.label ? '<div class="acc-card__label">' + o.label + '</div>' : '')
                    + '<div class="acc-card__price">' + o.price + '</div>'
                    + '<div class="acc-card__sub">' + (o.sub || '') + '</div>'
                    + (o.badge ? '<span class="acc-card__badge">' + o.badge + '</span>' : '')
                    + '</button>';
            }

            // Меньше устройств, чем сейчас, — только вместе с продлением.
            // Без продления меньше «сейчас + 1» не выбрать (платить не за что); с продлением — от базовых.
            function minN() { return cfg.paidActive && key === '' ? Math.min(maxDev, current + 1) : base; }
            const fewerNeedsRenewal = () => { if (cfg.paidActive && n < current && key === '') key = cfg.terms[0]?.key || ''; };
            $('pay-terms').addEventListener('click', (e) => {
                const b = e.target.closest('[data-key]');
                if (!b) return;
                key = b.getAttribute('data-key');
                if (key === '' && n <= current) n = Math.min(maxDev, current + 1);
                render();
            });
            $('pay-devices').addEventListener('click', (e) => {
                const b = e.target.closest('[data-n]');
                if (!b || b.classList.contains('is-off')) return;
                n = parseInt(b.getAttribute('data-n'), 10);
                fewerNeedsRenewal();
                render();
            });
            $('pay-minus').addEventListener('click', () => { n = Math.max(minN(), n - 1); render(); });
            $('pay-plus').addEventListener('click', () => { n = Math.min(maxDev, n + 1); render(); });

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

<x-cabinet-layout>
    @include('partials.account-styles')

    @php
        /** @var array<string, mixed> $account */
        /** @var array<string, mixed> $payConfig */
        $state = $account['state'];
    @endphp

    <div class="max-w-4xl mx-auto acc" id="acc-pay" data-config="{{ json_encode($payConfig, JSON_UNESCAPED_UNICODE) }}" data-link="{{ route('cabinet.payment.link') }}" data-add="{{ request('add') === 'device' ? '1' : '0' }}">
        {{-- Состояние подписки --}}
        <div class="acc-block">
            <div class="acc-status">
                @if ($state === 'active')
                    <span class="acc-pill acc-pill--on">Активна</span>
                @elseif ($state === 'trial')
                    <span class="acc-pill acc-pill--trial">Пробный период</span>
                @elseif ($state === 'inactive')
                    <span class="acc-pill acc-pill--off">Не активна</span>
                @else
                    <span class="acc-pill acc-pill--off">Не оформлена</span>
                @endif

                @if ($account['expires_at'] && in_array($state, ['active', 'trial'], true))
                    <span class="acc-status__until">до {{ $account['expires_at']->format('d.m.Y') }}</span>
                    <span class="acc-status__left">осталось {{ $account['days_left'] }} дн.</span>
                @elseif ($state === 'inactive' && $account['expires_at'])
                    <span class="acc-status__left">закончилась {{ $account['expires_at']->format('d.m.Y') }}</span>
                @elseif (in_array($state, ['active', 'trial'], true))
                    <span class="acc-status__until">без срока</span>
                @endif
            </div>
        </div>

        {{-- Срок подписки --}}
        <div class="acc-block">
            <div class="acc-chips">
                <span class="acc-chip">Срок подписки</span>
                <span class="acc-chip acc-chip--ghost">Трафик безлимит</span>
            </div>
            <div class="acc-cards" id="acc-terms"></div>
        </div>

        {{-- Устройства --}}
        <div class="acc-block" id="acc-devices-block">
            <div class="acc-chips">
                <span class="acc-chip">Устройства</span>
                <span class="acc-chip acc-chip--orange" id="acc-device-price"></span>
            </div>
            <div class="acc-cards" id="acc-devices" style="--acc-cols: 5;"></div>

            <div class="acc-row" id="acc-more-row" hidden>
                <span class="acc-row__label">Всего устройств</span>
                <div class="acc-stepper">
                    <button type="button" id="acc-more-minus" aria-label="Меньше устройств">−</button>
                    <output id="acc-more-value"></output>
                    <button type="button" id="acc-more-plus" aria-label="Больше устройств">+</button>
                </div>
            </div>

            <div class="acc-row" id="acc-months-row" hidden>
                <span class="acc-row__label">Доп. устройства оплатить на</span>
                <div class="acc-stepper">
                    <button type="button" id="acc-months-minus" aria-label="Меньше месяцев">−</button>
                    <output id="acc-months-value"></output>
                    <button type="button" id="acc-months-plus" aria-label="Больше месяцев">+</button>
                </div>
                <span class="acc-hint" id="acc-months-hint"></span>
            </div>
        </div>

        {{-- Итог --}}
        <div class="acc-block acc-sum">
            <div id="acc-lines"></div>
            <div class="acc-sum__total"><span>К оплате</span><span id="acc-total">0 ₽</span></div>
            <div class="acc-sum__until" id="acc-until"></div>
            <button type="button" class="acc-btn acc-btn--primary acc-btn--wide" id="acc-pay-btn">Оплатить</button>
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
            const fmtDate = (d) => d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });

            const base = cfg.baseDevices;
            const currentTotal = base + cfg.paidExtra;
            const cardMax = Math.min(cfg.maxDevices, Math.max(5, base));
            const termByKey = Object.fromEntries(cfg.terms.map((t) => [t.key, t]));

            // Выбор: term — ключ срока или '' («не продлевать»); devices — сколько устройств всего; months — срок оплаты доп. устройств.
            let term = cfg.terms.length ? cfg.terms[0].key : '';
            let devices = Math.max(base, currentTotal);
            let months = 0;
            let moreOpen = devices > cardMax;

            if (root.dataset.add === '1' && cfg.active && devices < cfg.maxDevices) {
                term = '';
                devices += 1;
            }
            months = defaultMonths();

            function defaultMonths() {
                if (devices <= base) return 0;
                const t = termByKey[term];
                return Math.min(cfg.deviceMaxMonths, t ? t.months : 1);
            }
            function minMonths() {
                // «Не оплачивать» можно, только если не просим устройств сверх уже оплаченных.
                return devices - base > cfg.paidExtra ? 1 : 0;
            }

            function render() {
                const t = termByKey[term] || null;
                const extra = Math.max(0, devices - base);
                if (extra === 0) months = 0;
                months = Math.max(minMonths(), Math.min(cfg.deviceMaxMonths, months));
                const paidExtraNow = months > 0 ? extra : 0;

                // Срок
                let html = '';
                if (cfg.active) {
                    html += card({ on: term === '', attr: 'data-term=""', big: '—', label: 'Не продлевать', price: '', sub: cfg.expiresAt ? 'действует до ' + cfg.expiresAt : 'без срока' });
                }
                cfg.terms.forEach((x) => {
                    html += card({
                        on: term === x.key, attr: 'data-term="' + x.key + '"',
                        big: x.months + '<small>' + plural(x.months, 'месяц', 'месяца', 'месяцев') + '</small>', label: '',
                        price: rub(x.amount_rub),
                        sub: x.months > 1 ? '≈ ' + rub(x.per_month_rub) + ' в месяц' : 'в месяц',
                        badge: x.discount_percent > 0 ? 'Скидка −' + x.discount_percent + '%' : '',
                    });
                });
                $('acc-terms').innerHTML = html;
                $('acc-terms').style.setProperty('--acc-cols', cfg.active ? 4 : 3);

                // Устройства
                const block = $('acc-devices-block');
                block.hidden = cfg.unlimitedDevices || cfg.maxDevices <= base;
                if (!block.hidden) {
                    $('acc-device-price').textContent = 'Доп. устройство ' + rub(cfg.deviceMonthRub) + ' в месяц';
                    let dh = '';
                    for (let n = base; n <= cardMax; n++) {
                        dh += card({
                            on: !moreOpen && devices === n, attr: 'data-devices="' + n + '"', tag: n === currentTotal ? 'Сейчас' : '',
                            big: String(n), label: plural(n, 'устройство', 'устройства', 'устройств'),
                            price: n === base ? '' : '+' + rub((n - base) * cfg.deviceMonthRub),
                            sub: n === base ? 'входит в подписку' : 'в месяц',
                        });
                    }
                    if (cfg.maxDevices > cardMax) {
                        dh += '<button type="button" class="acc-card acc-card--dashed' + (moreOpen ? ' is-on' : '') + '" data-more="1"><span class="acc-card__plus">+</span><span class="acc-card__label">Ещё устройства</span><span class="acc-card__sub">до ' + cfg.maxDevices + ' на подписке</span></button>';
                    }
                    $('acc-devices').innerHTML = dh;
                    $('acc-more-row').hidden = !moreOpen;
                    $('acc-more-value').textContent = devices + ' ' + plural(devices, 'устройство', 'устройства', 'устройств');
                    $('acc-more-minus').disabled = devices <= cardMax + 1;
                    $('acc-more-plus').disabled = devices >= cfg.maxDevices;

                    $('acc-months-row').hidden = extra === 0;
                    $('acc-months-value').textContent = months === 0 ? 'не оплачивать' : months + ' ' + plural(months, 'месяц', 'месяца', 'месяцев');
                    $('acc-months-minus').disabled = months <= minMonths();
                    $('acc-months-plus').disabled = months >= cfg.deviceMaxMonths;
                    $('acc-months-hint').textContent = months === 0
                        ? 'Оплаченные доп. устройства доработают свой срок.'
                        : 'У каждого доп. устройства свой счётчик. Он идёт, только пока подписка активна.';
                }

                // Итог
                const lines = [];
                let total = 0;
                if (t) { lines.push(['Подписка · ' + t.label, t.amount_rub]); total += t.amount_rub; }
                if (paidExtraNow > 0) {
                    const sum = paidExtraNow * months * cfg.deviceMonthRub;
                    lines.push(['Доп. устройства: ' + paidExtraNow + ' × ' + months + ' мес. × ' + rub(cfg.deviceMonthRub), sum]);
                    total += sum;
                }
                $('acc-lines').innerHTML = lines.map((l) => '<div class="acc-sum__line"><span>' + l[0] + '</span><span>' + rub(l[1]) + '</span></div>').join('');
                $('acc-total').textContent = rub(total);

                let until = '';
                if (t) {
                    const from = cfg.active && cfg.expiresAtIso ? new Date(cfg.expiresAtIso) : new Date();
                    if (cfg.active && !cfg.expiresAtIso) {
                        until = 'Подписка без срока';
                    } else {
                        from.setDate(from.getDate() + t.days);
                        until = 'Подписка будет до ' + fmtDate(from);
                    }
                } else if (cfg.active && cfg.expiresAt) {
                    until = 'Срок подписки не меняется: до ' + cfg.expiresAt;
                }
                $('acc-until').textContent = until;

                const btn = $('acc-pay-btn');
                btn.disabled = total === 0;
                btn.textContent = total === 0 ? 'Выберите, что оплатить' : 'Оплатить ' + rub(total);
            }

            function card(o) {
                return '<button type="button" class="acc-card' + (o.on ? ' is-on' : '') + '" ' + o.attr + '>'
                    + (o.tag ? '<span class="acc-card__tag">' + o.tag + '</span>' : '')
                    + '<span class="acc-card__radio"></span>'
                    + '<div class="acc-card__big">' + o.big + '</div>'
                    + (o.label ? '<div class="acc-card__label">' + o.label + '</div>' : '')
                    + (o.price ? '<div class="acc-card__price">' + o.price + '</div>' : '<div class="acc-card__price">&nbsp;</div>')
                    + '<div class="acc-card__sub">' + (o.sub || '') + '</div>'
                    + (o.badge ? '<span class="acc-card__badge">' + o.badge + '</span>' : '')
                    + '</button>';
            }

            $('acc-terms').addEventListener('click', (e) => {
                const b = e.target.closest('[data-term]');
                if (!b) return;
                term = b.getAttribute('data-term');
                if (devices > base && months > 0) months = defaultMonths();
                render();
            });
            $('acc-devices').addEventListener('click', (e) => {
                const more = e.target.closest('[data-more]');
                const b = e.target.closest('[data-devices]');
                if (more) { moreOpen = true; devices = Math.max(devices, cardMax + 1); }
                else if (b) { moreOpen = false; devices = parseInt(b.getAttribute('data-devices'), 10); }
                else return;
                if (months === 0) months = defaultMonths();
                render();
            });
            $('acc-more-minus').addEventListener('click', () => { devices--; render(); });
            $('acc-more-plus').addEventListener('click', () => { devices++; if (months === 0) months = defaultMonths(); render(); });
            $('acc-months-minus').addEventListener('click', () => { months--; render(); });
            $('acc-months-plus').addEventListener('click', () => { months++; render(); });

            $('acc-pay-btn').addEventListener('click', async () => {
                const btn = $('acc-pay-btn');
                const old = btn.textContent;
                btn.disabled = true;
                btn.textContent = '…';
                try {
                    const r = await fetch(root.dataset.link, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: JSON.stringify({ term: term || null, devices: cfg.unlimitedDevices ? 0 : devices, device_months: months }),
                    });
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok || !data.url) throw new Error(data.error || 'failed');
                    window.location.href = data.url;
                } catch (err) {
                    btn.disabled = false;
                    btn.textContent = old;
                    const messages = {
                        term_required: 'Подписка не активна — выберите срок.',
                        device_months_required: 'Укажите, на сколько месяцев оплатить новые устройства.',
                        devices_out_of_range: 'Столько устройств на одной подписке подключить нельзя.',
                        payments_not_configured: 'Оплата временно недоступна. Напишите в поддержку.',
                    };
                    alert(messages[String(err.message)] || 'Не удалось создать ссылку оплаты. Попробуйте ещё раз.');
                }
            });

            render();
        })();
    </script>
</x-cabinet-layout>

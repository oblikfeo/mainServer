<div class="lp-profile-block lp-profile-accordion lp-ref-section" x-data="{ open: false }">
    <button
        type="button"
        class="lp-profile-accordion__trigger"
        @click="open = !open"
        :aria-expanded="open"
        aria-controls="ref-history-panel"
        id="ref-history-title"
    >
        <span class="lp-profile-accordion__title">История начислений</span>
        <span class="lp-profile-accordion__chev" :class="{ 'lp-profile-accordion__chev--open': open }" aria-hidden="true">▾</span>
    </button>
    <div class="lp-profile-accordion__panel" id="ref-history-panel" x-show="open" x-cloak x-transition role="region" aria-labelledby="ref-history-title">

    <div class="lp-table-wrap lp-ref-table-wrap lp-ref-table-wrap--wide">
        <table class="lp-table lp-ref-table">
            <thead>
                <tr>
                    <th>Имя</th>
                    <th>Эл. почта</th>
                    <th>Статус бонуса</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Александр</td>
                    <td class="lp-mono text-slate-700">alex***@mail.ru</td>
                    <td><span class="lp-badge-pill lp-badge-pill--ok">Оплатил</span></td>
                </tr>
                <tr>
                    <td>Мария</td>
                    <td class="lp-mono text-slate-700">m***@yandex.ru</td>
                    <td><span class="lp-badge-pill lp-badge-pill--warn">Зарегистрирован</span></td>
                </tr>
                <tr>
                    <td>Иван</td>
                    <td class="lp-mono text-slate-700">ivan***@gmail.com</td>
                    <td><span class="lp-badge-pill lp-badge-pill--muted">Ожидание оплаты</span></td>
                </tr>
                <tr>
                    <td>Елена</td>
                    <td class="lp-mono text-slate-700">el***@inbox.ru</td>
                    <td><span class="lp-badge-pill lp-badge-pill--ok">Бонус начислен</span></td>
                </tr>
            </tbody>
        </table>
    </div>
    </div>
</div>

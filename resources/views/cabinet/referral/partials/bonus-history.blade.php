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

    <div class="lp-ref-history" role="list">
        <article class="lp-ref-history__card" role="listitem">
            <div class="lp-ref-history__head">
                <span class="lp-ref-history__name">Александр</span>
                <span class="lp-badge-pill lp-badge-pill--ok">Оплатил</span>
            </div>
            <p class="lp-ref-history__email"><span class="lp-ref-history__email-label">Почта</span> <span class="lp-ref-history__email-val">alex***@mail.ru</span></p>
        </article>
        <article class="lp-ref-history__card" role="listitem">
            <div class="lp-ref-history__head">
                <span class="lp-ref-history__name">Мария</span>
                <span class="lp-badge-pill lp-badge-pill--warn">Зарегистрирован</span>
            </div>
            <p class="lp-ref-history__email"><span class="lp-ref-history__email-label">Почта</span> <span class="lp-ref-history__email-val">m***@yandex.ru</span></p>
        </article>
        <article class="lp-ref-history__card" role="listitem">
            <div class="lp-ref-history__head">
                <span class="lp-ref-history__name">Иван</span>
                <span class="lp-badge-pill lp-badge-pill--muted">Ожидание оплаты</span>
            </div>
            <p class="lp-ref-history__email"><span class="lp-ref-history__email-label">Почта</span> <span class="lp-ref-history__email-val">ivan***@gmail.com</span></p>
        </article>
        <article class="lp-ref-history__card" role="listitem">
            <div class="lp-ref-history__head">
                <span class="lp-ref-history__name">Елена</span>
                <span class="lp-badge-pill lp-badge-pill--ok">Бонус начислен</span>
            </div>
            <p class="lp-ref-history__email"><span class="lp-ref-history__email-label">Почта</span> <span class="lp-ref-history__email-val">el***@inbox.ru</span></p>
        </article>
    </div>
    </div>
</div>

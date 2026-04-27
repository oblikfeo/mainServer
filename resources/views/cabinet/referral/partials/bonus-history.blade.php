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
        @forelse ($referralHistory as $row)
        <article class="lp-ref-history__card" role="listitem">
            <div class="lp-ref-history__head">
                <span class="lp-ref-history__name">{{ $row['name'] }}</span>
                @if ($row['status_kind'] === 'bonus')
                    <span class="lp-badge-pill lp-badge-pill--ok">Бонус начислен</span>
                @elseif ($row['status_kind'] === 'ok')
                    <span class="lp-badge-pill lp-badge-pill--ok">Оплатил</span>
                @else
                    <span class="lp-badge-pill lp-badge-pill--muted">Ожидание оплаты</span>
                @endif
            </div>
            <p class="lp-ref-history__email"><span class="lp-ref-history__email-label">Почта</span> <span class="lp-ref-history__email-val">{{ $row['email_masked'] }}</span></p>
        </article>
        @empty
        <p class="lp-ref-quests-lead">Пока никого не приглашали — поделитесь ссылкой выше.</p>
        @endforelse
    </div>
    </div>
</div>

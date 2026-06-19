@php
    $promoCopyId = 'lp-ref-promo-copy-'.md5($referralLink);
    $promoUrlId = 'lp-ref-promo-url-'.md5($referralLink);
@endphp

<div class="lp-buy-done-box lp-ref-promo-box" style="margin-top:24px;">
    <div class="lp-buy-done-box__label">Реферальная программа</div>
    <p class="lp-nice-hero__lead" style="margin-bottom:12px;text-align:left;">
        Поделитесь ссылкой с друзьями — за каждого приглашённого начисляются бонусы к подписке.
    </p>
    <code class="lp-buy-done-box__url" id="{{ $promoUrlId }}">{{ $referralLink }}</code>
    <div class="lp-buy-done-actions" style="margin-top:12px;">
        <button type="button" class="lp-buy-done-btn" id="{{ $promoCopyId }}">Скопировать ссылку</button>
        <a href="{{ route('cabinet.referral') }}" class="lp-buy-done-btn lp-buy-done-btn--secondary">Подробнее</a>
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById(@json($promoCopyId));
    var urlEl = document.getElementById(@json($promoUrlId));
    if (!btn || !urlEl) return;
    btn.addEventListener('click', async function () {
        var text = urlEl.textContent || '';
        try {
            await navigator.clipboard.writeText(text);
            btn.textContent = 'Скопировано';
            setTimeout(function () { btn.textContent = 'Скопировать ссылку'; }, 1600);
        } catch (_) {
            alert('Не удалось скопировать. Выделите ссылку вручную.');
        }
    });
})();
</script>

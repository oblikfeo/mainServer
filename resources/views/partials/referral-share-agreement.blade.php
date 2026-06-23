@php
    $copyId = 'lp-ref-agreement-copy-'.md5($referralLink);
    $urlId = 'lp-ref-agreement-url-'.md5($referralLink);
@endphp

<section class="lp-agreement-section" aria-labelledby="spasibo-ref-title">
    <h2 id="spasibo-ref-title">Реферальная программа</h2>
    <p>Поделитесь ссылкой с друзьями — за каждого приглашённого начисляются бонусы к подписке.</p>
    <code class="lp-ref-agreement-link" id="{{ $urlId }}">{{ $referralLink }}</code>
    <div class="lp-ref-agreement-actions">
        <button type="button" class="lp-ref-agreement-copy" id="{{ $copyId }}">Скопировать ссылку</button>
        <a href="{{ route('cabinet.referral') }}" class="lp-ref-agreement-more">Подробнее в кабинете</a>
    </div>
</section>

<script>
(function () {
    var btn = document.getElementById(@json($copyId));
    var urlEl = document.getElementById(@json($urlId));
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

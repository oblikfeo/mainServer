@php
    $brand = config('marketing.brand_name', 'Надежда');
    $shareText = 'Присоединяйтесь к «'.$brand.'»: '.$referralLink;
    $wa = 'https://wa.me/?text='.rawurlencode($shareText);
    $viber = 'viber://forward?text='.rawurlencode($shareText);
    $telegram = 'https://t.me/share/url?url='.rawurlencode($referralLink).'&text='.rawurlencode('Присоединяйтесь к «'.$brand.'»');
    $max = 'https://max.ru/:share?text='.rawurlencode($shareText);
@endphp

<div class="lp-profile-block lp-profile-accordion lp-ref-section" x-data="{ open: true }">
    <button
        type="button"
        class="lp-profile-accordion__trigger"
        @click="open = !open"
        :aria-expanded="open"
        aria-controls="ref-share-panel"
        id="ref-share-title"
    >
        <span class="lp-profile-accordion__title">Поделиться ссылкой</span>
        <span class="lp-profile-accordion__chev" :class="{ 'lp-profile-accordion__chev--open': open }" aria-hidden="true">▾</span>
    </button>
    <div class="lp-profile-accordion__panel" id="ref-share-panel" x-show="open" x-cloak x-transition role="region" aria-labelledby="ref-share-title">

    <div class="lp-ref-share lp-ref-share--many">
        <a class="lp-ref-share__btn lp-ref-share__btn--wa" href="{{ $wa }}" target="_blank" rel="noopener noreferrer">
            <span class="lp-ref-share__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </span>
            <span class="lp-ref-share__label">WhatsApp</span>
            <span class="lp-ref-share__sub">Отправить текст с ссылкой</span>
        </a>
        <a class="lp-ref-share__btn lp-ref-share__btn--viber" href="{{ $viber }}" rel="noopener noreferrer">
            <span class="lp-ref-share__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28"><path d="M11.4 0C5.1 0 .2 4.7.2 10.4c0 2.8 1.2 5.4 3.2 7.3.4.4.6.8.5 1.3l-.6 2.4c-.1.5.3 1 .9 1.1h.3l2.9-1.1c.4-.1.8-.1 1.2.1 1.1.4 2.3.7 3.6.7 6.3 0 11.3-4.7 11.3-10.4C24.2 4.7 18.9 0 11.4 0zm6.1 14.4c-.3.8-1.5 1.5-2.6 1.7-.7.1-1.6.2-4.7-1-3.7-1.7-6.1-5.2-6.3-5.4-.2-.3-1.5-2-1.5-3.8 0-1.8.9-2.7 1.3-3.1.3-.3.8-.5 1.1-.5h.9c.3 0 .7 0 1 .7.4.8 1.2 2.6 1.3 2.8.1.2.2.4 0 .7-.1.2-.2.4-.4.6l-.6.6c-.2.2-.2.4-.1.6.1.2.6 1 1.3 1.9.9 1 1.6 1.3 2 1.5.2.1.4 0 .6-.2l.7-.8c.2-.2.5-.3.8-.2.3.1 1.9.9 2.2 1.1.3.2.5.3.6.5.1.2.1 1.1-.2 2z"/></svg>
            </span>
            <span class="lp-ref-share__label">Viber</span>
            <span class="lp-ref-share__sub">Передать сообщение</span>
        </a>
        <a class="lp-ref-share__btn lp-ref-share__btn--tg" href="{{ $telegram }}" target="_blank" rel="noopener noreferrer">
            <span class="lp-ref-share__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
            </span>
            <span class="lp-ref-share__label">Telegram</span>
            <span class="lp-ref-share__sub">Поделиться в Telegram</span>
        </a>
        <a class="lp-ref-share__btn lp-ref-share__btn--max" href="{{ $max }}" target="_blank" rel="noopener noreferrer">
            <span class="lp-ref-share__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6.17L4 18.17V4h16v12z"/></svg>
            </span>
            <span class="lp-ref-share__label">MAX</span>
            <span class="lp-ref-share__sub">Отправить в MAX</span>
        </a>
    </div>

    <div class="lp-ref-share-copy">
        <button
            type="button"
            class="lp-ref-share__copy-btn"
            x-data="{ copied: false }"
            x-on:click="async () => { try { await navigator.clipboard.writeText(@js($referralLink)); copied = true; setTimeout(() => copied = false, 1800); } catch (e) {} }"
        >
            <span x-show="!copied">Скопировать ссылку</span>
            <span x-show="copied" x-cloak>Скопировано</span>
        </button>
    </div>
    </div>
</div>

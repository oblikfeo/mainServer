<div class="lp-profile-block lp-profile-referral">
    <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 mb-0">Реферальная программа</h2>
    <p class="mt-2 text-sm font-semibold text-slate-800 leading-snug">
        Поделитесь ссылкой — друг сможет зарегистрироваться по ней. Ниже показано, сколько человек пришло по вашей ссылке и сколько из них оплатило подписку.
    </p>

    <div class="lp-copy-row mt-4">
        <label class="block text-xs font-black uppercase tracking-wider text-slate-600">Ваша реферальная ссылка</label>
        <textarea
            class="lp-referral-url-field"
            readonly
            rows="2"
        >{{ $referralLink }}</textarea>
        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                class="lp-btn lp-btn--copy"
                x-data="{ copied: false }"
                x-on:click="async () => { try { await navigator.clipboard.writeText(@js($referralLink)); copied = true; setTimeout(() => copied = false, 1600); } catch (e) {} }"
                x-bind:class="{ 'lp-btn--copied': copied }"
            >
                <span x-show="!copied">Скопировать ссылку</span>
                <span x-show="copied" x-cloak>Скопировано</span>
            </button>
        </div>
        <span class="lp-copy-hint">Отправьте ссылку в мессенджер или почту.</span>
    </div>

    <dl class="lp-dl-grid lp-dl-grid--referral-stats mt-5">
        <div>
            <dt>Зарегистрировалось</dt>
            <dd class="tabular-nums">{{ $referralsRegistered }}</dd>
        </div>
        <div>
            <dt>Оплатили подписку</dt>
            <dd class="tabular-nums">{{ $referralsPaid }}</dd>
        </div>
    </dl>
</div>

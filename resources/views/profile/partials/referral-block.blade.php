<div class="lp-profile-block lp-profile-referral">
    <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 mb-0">Реферальная программа</h2>
    <p class="mt-2 text-sm font-semibold text-slate-800 leading-snug">
        Поделитесь ссылкой — друг сможет зарегистрироваться по ней.
    </p>

    <div class="mt-4">
        <button
            type="button"
            class="lp-referral-url-click"
            x-data="{ copied: false }"
            x-on:click="async () => { try { await navigator.clipboard.writeText(@js($referralLink)); copied = true; setTimeout(() => copied = false, 1600); } catch (e) {} }"
        >
            <span class="lp-referral-url-click__text">{{ $referralLink }}</span>
            <span class="lp-referral-url-click__badge" x-show="copied" x-cloak>Скопировано</span>
        </button>
    </div>

    <div class="lp-referral-metrics">
        <div class="lp-referral-metric">
            <span class="lp-referral-metric__label">Зарегистрировалось</span>
            <span class="lp-referral-metric__value tabular-nums">{{ $referralsRegistered }}</span>
        </div>
        <div class="lp-referral-metric">
            <span class="lp-referral-metric__label">Оплатили подписку</span>
            <span class="lp-referral-metric__value tabular-nums">{{ $referralsPaid }}</span>
        </div>
    </div>
</div>

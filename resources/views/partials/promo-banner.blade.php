{{-- Акция «месяц + 10 устройств» над блоком цен — карточка цены из дизайн-системы (pricing-card).
     На главной ($mode = 'home') — ссылки на оплату, на /buy ($mode = 'buy') — кнопка оплаты. --}}
@php
    $promo = \App\Services\Account\AccountPricing::promo();
    $mode = $mode ?? 'home';
    $fmt = static fn (int $n): string => number_format($n, 0, ',', ' ');
@endphp
@if ($promo)
    <style>
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-promo { display: grid; gap: 14px; align-items: center; margin: 0 0 40px; }
        @media (min-width: 768px) {
            .lp-f1 .lp-pricing.lp-pricing-mock .lp-promo { grid-template-columns: minmax(0, 1fr) 280px; gap: 30px; text-align: left; margin-bottom: 50px; }
        }
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-promo .pricing-price s { font-size: 0.45em; color: #888; text-decoration-thickness: 3px; margin-left: 8px; }
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-promo__btns { display: flex; flex-direction: column; gap: 10px; }
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-promo__btn { display: inline-flex; align-items: center; justify-content: center; width: 100%; margin: 0; padding: 14px 16px; border: var(--mock-border); background: var(--mock-primary); color: #fff; font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; text-decoration: none; cursor: pointer; }
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-promo__btn:hover { transform: translate(-2px, -2px); box-shadow: 4px 4px 0 var(--mock-dark); }
        .lp-f1 .lp-pricing.lp-pricing-mock .lp-promo__btn--ghost { background: #fff; color: var(--mock-dark); }
    </style>
    <div class="pricing-card pricing-card-best lp-promo" id="lp-promo">
        <div>
            <div class="pricing-duration">{{ $promo['label'] }}</div>
            <div class="pricing-price">{{ $fmt($promo['amount_rub']) }}&nbsp;₽@if ($promo['saving_rub'] > 0)<s>{{ $fmt($promo['regular_rub']) }}&nbsp;₽</s>@endif</div>
            @if ($promo['saving_rub'] > 0)
                <div class="pricing-savings">Выгода {{ $fmt($promo['saving_rub']) }}&nbsp;₽</div>
            @endif
        </div>
        <div class="lp-promo__btns">
            @if ($mode === 'buy')
                <button type="button" class="lp-promo__btn lp-buy-pay-btn" data-term="promo" data-promo-devices="{{ $promo['devices'] }}" data-promo-amount="{{ $promo['amount_rub'] }}">
                    Оплатить {{ $fmt($promo['amount_rub']) }}&nbsp;₽
                </button>
            @else
                <a href="{{ route('cabinet.payment') }}" class="lp-promo__btn">Подключить за {{ $fmt($promo['amount_rub']) }}&nbsp;₽</a>
                <a href="{{ route('quick_buy.show') }}#lp-promo" class="lp-promo__btn lp-promo__btn--ghost">Без регистрации</a>
            @endif
        </div>
    </div>
@endif

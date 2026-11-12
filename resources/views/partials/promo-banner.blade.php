{{-- Акция «месяц + 10 устройств» над блоком цен: на главной ($mode = 'home') — ссылки на оплату, на /buy ($mode = 'buy') — кнопка оплаты --}}
@php
    $promo = \App\Services\Account\AccountPricing::promo();
    $mode = $mode ?? 'home';
    $fmt = static fn (int $n): string => number_format($n, 0, ',', ' ');
@endphp
@if ($promo)
    <style>
        .lp-f1 .lp-promo { position: relative; display: grid; gap: 18px; align-items: center; margin: 0 0 40px; padding: 28px 22px 24px; background: #bff000; border: 4px solid #1a1a1a; box-shadow: 8px 8px 0 #1a1a1a; color: #1a1a1a; text-align: left; }
        @media (min-width: 768px) { .lp-f1 .lp-promo { grid-template-columns: minmax(0, 1fr) auto; gap: 32px; padding: 34px 36px 30px; margin-bottom: 50px; } }
        .lp-f1 .lp-promo__tag { position: absolute; top: -14px; left: 22px; background: #ff4d00; color: #fff; border: 3px solid #1a1a1a; padding: 3px 12px; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em; }
        .lp-f1 .lp-promo__title { margin: 0; font-family: "Syne", ui-sans-serif, system-ui, sans-serif; font-size: 28px; font-weight: 800; line-height: 1.05; text-transform: uppercase; }
        @media (min-width: 768px) { .lp-f1 .lp-promo__title { font-size: 40px; } }
        .lp-f1 .lp-promo__list { margin: 12px 0 0; padding: 0; list-style: none; font-size: 16px; font-weight: 700; }
        .lp-f1 .lp-promo__list li { margin-top: 4px; }
        .lp-f1 .lp-promo__list li::before { content: "✓ "; font-weight: 900; }
        .lp-f1 .lp-promo__buy { display: flex; flex-direction: column; gap: 10px; min-width: 240px; }
        .lp-f1 .lp-promo__price { display: flex; align-items: baseline; gap: 12px; flex-wrap: wrap; }
        .lp-f1 .lp-promo__new { font-family: "Syne", ui-sans-serif, system-ui, sans-serif; font-size: 48px; font-weight: 800; line-height: 1; }
        .lp-f1 .lp-promo__old { font-size: 20px; font-weight: 800; opacity: 0.55; text-decoration: line-through; text-decoration-thickness: 3px; }
        .lp-f1 .lp-promo__save { align-self: flex-start; background: #1a1a1a; color: #fff; padding: 3px 10px; font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em; }
        .lp-f1 .lp-promo__btn { display: inline-flex; align-items: center; justify-content: center; width: 100%; margin: 4px 0 0; padding: 15px 18px; border: 3px solid #1a1a1a; background: #ff4d00; color: #fff; font-family: "Space Grotesk", ui-sans-serif, system-ui, sans-serif; font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; text-decoration: none; cursor: pointer; box-shadow: 4px 4px 0 #1a1a1a; }
        .lp-f1 .lp-promo__btn:hover { transform: translate(-2px, -2px); box-shadow: 6px 6px 0 #1a1a1a; }
        .lp-f1 .lp-promo__btn--ghost { background: #fff; color: #1a1a1a; }
        .lp-f1 .lp-promo__or { margin: 0 0 18px; text-align: center; color: #fff; font-size: 13px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.12em; }
    </style>
    <div class="lp-promo" id="lp-promo">
        <span class="lp-promo__tag">🔥 Акция</span>
        <div>
            <h3 class="lp-promo__title">{{ $promo['label'] }}</h3>
            <ul class="lp-promo__list">
                <li>Подписка на {{ $promo['days'] }} дней</li>
                <li>До {{ $promo['devices'] }} устройств одновременно — на всю семью</li>
                <li>Трафик без лимита</li>
            </ul>
        </div>
        <div class="lp-promo__buy">
            <div class="lp-promo__price">
                <span class="lp-promo__new">{{ $fmt($promo['amount_rub']) }}&nbsp;₽</span>
                @if ($promo['saving_rub'] > 0)
                    <span class="lp-promo__old">{{ $fmt($promo['regular_rub']) }}&nbsp;₽</span>
                @endif
            </div>
            @if ($promo['saving_rub'] > 0)
                <span class="lp-promo__save">Выгода {{ $fmt($promo['saving_rub']) }}&nbsp;₽</span>
            @endif
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
    <p class="lp-promo__or">или обычная подписка</p>
@endif

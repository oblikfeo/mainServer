{{-- /buy: выбор числа устройств + срок подписки с кнопкой «Оплатить» --}}
@php
    $terms = array_values(\App\Services\Account\AccountPricing::terms());
    $baseDevices = \App\Services\Account\AccountPricing::baseDevices();
    $maxDevices = \App\Services\Account\AccountPricing::maxDevices();
    $deviceRub = \App\Services\Account\AccountPricing::deviceMonthRub();
    $lastIndex = count($terms) - 1;
@endphp
<div class="pricing-column">
    <h3 class="pricing-column-title">Устройства</h3>
    <p class="pricing-column-hint">{{ $baseDevices }} входят в подписку</p>
    <div class="pricing-cards">
        <div
            class="pricing-card lp-buy-devices"
            id="lp-buy-devices"
            data-base="{{ $baseDevices }}"
            data-max="{{ $maxDevices }}"
            data-device-rub="{{ $deviceRub }}"
        >
            <div class="pricing-duration">Сколько устройств</div>
            <div class="lp-buy-devices__stepper">
                <button type="button" class="lp-buy-devices__btn" id="lp-buy-devices-minus" aria-label="Меньше устройств">&minus;</button>
                <span class="lp-buy-devices__count" id="lp-buy-devices-count" aria-live="polite">{{ $baseDevices }}</span>
                <button type="button" class="lp-buy-devices__btn" id="lp-buy-devices-plus" aria-label="Больше устройств">+</button>
            </div>
            <div class="pricing-per-month">+{{ $deviceRub }}&nbsp;₽ в месяц за каждое дополнительное</div>
            <div class="pricing-note">До {{ $maxDevices }} устройств на одном аккаунте. Добавить устройство можно и позже — в личном кабинете.</div>
        </div>
    </div>
</div>
<div class="pricing-column">
    <h3 class="pricing-column-title">Срок</h3>
    <p class="pricing-column-hint" id="lp-buy-devices-hint">{{ $baseDevices }} устройства</p>
    <div class="pricing-cards">
        @foreach ($terms as $i => $term)
            <div class="pricing-card {{ $i === $lastIndex ? 'pricing-card-popular' : '' }}">
                <div class="pricing-duration">{{ $term['label'] }}</div>
                <div class="pricing-price"><span data-buy-price>{{ $term['amount_rub'] }}</span>&nbsp;₽</div>
                @if ($term['discount_percent'] > 0)
                    <div class="pricing-savings">Скидка {{ $term['discount_percent'] }}% на подписку</div>
                @endif
                <button
                    type="button"
                    class="lp-buy-pay-btn"
                    data-term="{{ $term['key'] }}"
                    data-term-months="{{ $term['months'] }}"
                    data-term-amount="{{ $term['amount_rub'] }}"
                >
                    Оплатить
                </button>
            </div>
        @endforeach
    </div>
</div>

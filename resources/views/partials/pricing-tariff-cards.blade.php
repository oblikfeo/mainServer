@php
    $showPayButtons = $showPayButtons ?? false;
@endphp
@foreach (config('marketing.tariffs', []) as $plan)
    <article
        class="lp-tariff-card lp-tariff-card--{{ $plan['kind'] }}"
        aria-labelledby="{{ $plan['aria_id'] }}"
    >
        <header class="lp-tariff-card__head">
            <h3 class="lp-tariff-card__title" id="{{ $plan['aria_id'] }}">{{ $plan['title'] }}</h3>
            <p class="lp-tariff-card__meta">{{ $plan['meta'] }}</p>
        </header>
        <div class="lp-tariff-card__body">
            @foreach ($plan['rows'] as $rowIndex => $row)
                @if ($showPayButtons)
                    <div class="lp-tariff-card__row lp-tariff-card__row--with-pay">
                        <div class="lp-tariff-card__row-left">
                            <span class="lp-tariff-card__period">{{ $row['period'] }}</span>
                            <div class="lp-tariff-card__price-block">
                                @include('partials.pricing-tariff-price-block', ['row' => $row])
                            </div>
                        </div>
                        <button
                            type="button"
                            class="lp-cab-pay-btn"
                            data-tariff-plan="{{ $plan['id'] }}"
                            data-tariff-period="{{ $row['period'] }}"
                            data-tariff-amount="{{ $row['amount'] }}"
                        >
                            Оплатить
                        </button>
                    </div>
                @else
                    <div class="lp-tariff-card__row">
                        <span class="lp-tariff-card__period">{{ $row['period'] }}</span>
                        <div class="lp-tariff-card__price-block">
                            @include('partials.pricing-tariff-price-block', ['row' => $row])
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </article>
@endforeach

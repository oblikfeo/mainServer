{{-- Карточки цен с кнопкой «Оплатить» для /buy --}}
@foreach (config('marketing.tariffs', []) as $plan)
    @php
        $rows = $plan['rows'] ?? [];
        $rowCount = count($rows);
    @endphp
    <div class="pricing-column">
        <h3 class="pricing-column-title">{{ $plan['title'] }}</h3>
        @if (filled($plan['pricing_hint'] ?? null))
            <p class="pricing-column-hint">{{ $plan['pricing_hint'] }}</p>
        @endif
        <div class="pricing-cards">
            @foreach ($rows as $i => $row)
                @php
                    $isLast = $i === $rowCount - 1;
                    $sub = $row['sub'] ?? null;
                    $plainSub = trim(strip_tags((string) $sub));
                    $classes = ['pricing-card'];
                    if (! empty($row['badge'] ?? null)) {
                        $classes[] = 'pricing-card-best';
                    } elseif (($plan['kind'] ?? '') === 'solo' && $isLast) {
                        $classes[] = 'pricing-card-popular';
                    }
                    $showSavings = filled($sub) && str_contains($plainSub, 'Выгода');
                    $showPerMonth = filled($sub) && ! $showSavings && (
                        str_contains($plainSub, '/мес') || str_contains($plainSub, '₽/мес')
                    );
                    $showRemainderSub = filled($sub) && ! $showSavings && ! $showPerMonth;
                @endphp
                <div class="{{ implode(' ', $classes) }}">
                    @if (filled($row['badge'] ?? null))
                        <span class="pricing-tag">{{ $row['badge'] }}</span>
                    @endif
                    <div class="pricing-duration">{{ $row['period'] }}</div>
                    <div class="pricing-price">{{ $row['amount'] }}&nbsp;₽</div>
                    @if ($showSavings)
                        <div class="pricing-savings">{!! $sub !!}</div>
                    @elseif ($showPerMonth)
                        <div class="pricing-per-month">{!! $sub !!}</div>
                    @elseif ($showRemainderSub)
                        <div class="pricing-note">{!! $sub !!}</div>
                    @endif
                    @if (filled($row['note'] ?? null))
                        <div class="pricing-note">{!! $row['note'] !!}</div>
                    @endif
                    <button
                        type="button"
                        class="lp-buy-pay-btn"
                        data-tariff-plan="{{ $plan['id'] }}"
                        data-tariff-period="{{ $row['period'] }}"
                        data-tariff-amount="{{ $row['amount'] }}"
                    >
                        Оплатить
                    </button>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

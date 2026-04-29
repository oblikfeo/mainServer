{{-- Карточки цен как в nadezhda.html; суммы из config(marketing.tariffs) --}}
@foreach (config('marketing.tariffs', []) as $plan)
    @php
        $rows = $plan['rows'] ?? [];
        $rowCount = count($rows);
    @endphp
    <div class="pricing-column">
        <h3 class="pricing-column-title">{{ $plan['title'] }}</h3>
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
                    $showNote = filled($sub) && ! $showSavings && ! $showPerMonth;
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
                    @elseif ($showNote)
                        <div class="pricing-note">{!! $sub !!}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endforeach

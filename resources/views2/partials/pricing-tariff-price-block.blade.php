@if (! empty($row['stack'] ?? false))
    <span class="lp-tariff-card__amount-line lp-tariff-card__amount-line--stack">
        <span class="lp-tariff-card__amount">{{ $row['amount'] }}&nbsp;₽</span>
        <span class="lp-badge">{{ $row['badge'] ?? '' }}</span>
    </span>
    <span class="lp-price-sub">{!! $row['sub'] ?? '' !!}</span>
@else
    <span class="lp-tariff-card__amount">{{ $row['amount'] }}&nbsp;₽</span>
    @if (filled($row['sub'] ?? null))
        <span class="lp-price-sub">{!! $row['sub'] !!}</span>
    @endif
@endif

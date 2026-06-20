@if (! empty($partnerLogo))
    <div class="lp-partner-logo">
        <img
            src="{{ asset($partnerLogo) }}"
            alt="{{ $partnerLabel }}"
            class="lp-partner-logo__img"
            loading="eager"
            decoding="async"
        >
    </div>
@else
    <span class="lp-partner-invite__name" role="note" aria-label="{{ $partnerLabel }}">{{ mb_strtoupper($partnerLabel, 'UTF-8') }}</span>
@endif

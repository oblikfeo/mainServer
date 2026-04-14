<div class="lp-payment-info">
    @foreach (config('marketing.payment_notes', []) as $line)
        <span>✓ {!! $line !!}</span>
    @endforeach
</div>

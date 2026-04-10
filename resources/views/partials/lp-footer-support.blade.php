@php
    $tg = config('marketing.telegram_support_url', config('marketing.telegram_url', 'https://t.me/nadezhda_tehsup'));
    $phone = config('marketing.support_phone', '');
    $address = config('marketing.support_address', '');
    $mail = config('marketing.support_email', '');
@endphp
<div class="lp-footer-support">
    <div>Служба поддержки: <a href="{{ $tg }}" target="_blank" rel="noopener noreferrer" class="text-inherit underline underline-offset-2">Telegram</a></div>
    @if (filled($phone))
        <div><a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="text-inherit underline underline-offset-2">{{ $phone }}</a></div>
    @endif
    @if (filled($address))
        <div>{{ $address }}</div>
    @endif
    @if (filled($mail))
        <div><a href="mailto:{{ $mail }}" class="text-inherit underline underline-offset-2">{{ $mail }}</a></div>
    @endif
</div>

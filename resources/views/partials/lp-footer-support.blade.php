@php
    $tg = config('marketing.telegram_support_url', config('marketing.telegram_url', 'https://t.me/nadezhda_tehsup'));
    $mail = config('marketing.support_email', '');
@endphp
<div class="lp-footer-support">
    <div>Служба поддержки: <a href="{{ $tg }}" target="_blank" rel="noopener noreferrer" class="text-inherit underline underline-offset-2">Telegram</a></div>
    @if (filled($mail))
        <div><a href="mailto:{{ $mail }}" class="text-inherit underline underline-offset-2">{{ $mail }}</a></div>
    @endif
</div>

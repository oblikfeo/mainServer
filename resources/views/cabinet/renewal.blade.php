<x-cabinet-layout>
    <div class="max-w-4xl mx-auto lp-renew-page">

        @if ($renewalSubscriptions->isEmpty())
            <div class="lp-empty lp-empty--compact">
                <p>Платных подписок пока нет — сначала оформите новую или дождитесь привязки существующей к аккаунту.</p>
                <p class="lp-text-muted-tight">
                    Если доступ уже есть, но не виден здесь — войдите с тем же email, что указывали при покупке, или напишите в поддержку.
                </p>
                <a href="{{ route('cabinet.payment') }}" class="lp-btn">К тарифам — новая подписка</a>
            </div>
        @else
            <div class="lp-renew-stack">
                @include('partials.pricing-renewal-cards', [
                    'renewalSubscriptions' => $renewalSubscriptions,
                    'soloDeviceCap' => $soloDeviceCap,
                ])
            </div>
        @endif
    </div>

    @include('partials.cabinet-wata-payment-script')
</x-cabinet-layout>

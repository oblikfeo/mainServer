<x-cabinet-layout>
    <div class="max-w-4xl mx-auto">
        <h1 class="lp-page-title">Оплата</h1>

        <div class="lp-pricing lp-pricing--cabinet">
            <h2 class="lp-section-title">Тарифы</h2>
            <div class="lp-tariff-cards">
                @include('partials.pricing-tariff-cards', ['showPayButtons' => true])
            </div>
            @include('partials.pricing-payment-notes')
        </div>
    </div>
</x-cabinet-layout>

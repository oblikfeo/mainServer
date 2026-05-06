<x-cabinet-layout>
    <div class="max-w-4xl mx-auto">
        <h2 class="lp-page-section-title">Тарифы и оплата</h2>
        <div class="lp-tariff-cards">
            @include('partials.pricing-tariff-cards', ['showPayButtons' => true])
        </div>
    </div>

    @include('partials.cabinet-wata-payment-script')
</x-cabinet-layout>

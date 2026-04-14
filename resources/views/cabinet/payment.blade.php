<x-cabinet-layout>
    <div class="max-w-4xl mx-auto">
        <h1 class="lp-page-title">Тарифы и оплата</h1>
        <div class="lp-tariff-cards">
            @include('partials.pricing-tariff-cards', ['showPayButtons' => true])
        </div>
    </div>
</x-cabinet-layout>

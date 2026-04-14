<x-cabinet-layout>
    <div class="max-w-4xl mx-auto">
        <h1 class="lp-page-title">Оплата</h1>

        <div class="lp-card" style="margin-bottom: 1.25rem;">
            <div class="lp-card__body lp-stack" style="padding: 1rem 1.1rem;">
                <p class="text-sm font-semibold text-slate-800 m-0 leading-relaxed">
                    Выберите период и тариф — оплата картой или через СБП будет доступна здесь после подключения ЮKassa.
                </p>
            </div>
        </div>

        <div class="lp-pricing lp-pricing--cabinet">
            <h2 class="lp-section-title">Тарифы</h2>
            <div class="lp-tariff-cards">
                @include('partials.pricing-tariff-cards', ['showPayButtons' => true])
            </div>
            @include('partials.pricing-payment-notes')
        </div>
    </div>
</x-cabinet-layout>

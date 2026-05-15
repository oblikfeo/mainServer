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

    @if ($renewalSubscriptions->isNotEmpty())
        <script>
            (function () {
                function flashRenewCardFromHash() {
                    var hash = window.location.hash;
                    if (!hash || hash.length < 2) return;
                    var id = decodeURIComponent(hash.slice(1));
                    if (!/^renew-sub-\d+-title$/.test(id)) return;

                    var el = document.getElementById(id);
                    if (!el) return;

                    var card = el.closest('.lp-renew-card');
                    if (!card) return;

                    requestAnimationFrame(function () {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        card.classList.add('lp-renew-card--flash');

                        var done = false;
                        function cleanup() {
                            if (done) return;
                            done = true;
                            card.classList.remove('lp-renew-card--flash');
                        }
                        card.addEventListener('animationend', cleanup, { once: true });
                        window.setTimeout(cleanup, 1800);
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', flashRenewCardFromHash);
                } else {
                    flashRenewCardFromHash();
                }
            })();
        </script>
    @endif
</x-cabinet-layout>

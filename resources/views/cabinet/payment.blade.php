<x-cabinet-layout>
    <div class="max-w-4xl mx-auto">
        <h2 class="lp-page-section-title">Новая подписка</h2>
        <div class="lp-tariff-cards">
            @include('partials.pricing-tariff-cards', ['showPayButtons' => true])
        </div>

        <div class="lp-pay-renewal-block">
            <h2 class="lp-page-section-title">Продление подписки</h2>

            @if ($renewalSubscriptions->isEmpty())
                <div class="lp-empty lp-empty--compact">
                    <p>Платных подписок пока нет — сначала оформите новую подписку или дождитесь привязки существующей к аккаунту.</p>
                    <p class="lp-text-muted-tight">
                        Если доступ уже есть, но не виден здесь — войдите с тем же email, что указывали при покупке, или напишите в поддержку.
                    </p>
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
    </div>

    <script>
        (function () {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            async function createLink(plan, period, purpose, subscriptionId) {
                const body = { plan, period, purpose };
                if (purpose === 'renew') {
                    if (!subscriptionId) {
                        throw new Error('subscription_required');
                    }
                    body.subscription_id = subscriptionId;
                }

                const r = await fetch('{{ route('cabinet.payment.link') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok || !data || !data.url) {
                    if (data && data.error === 'plan_mismatch') {
                        throw new Error('plan_mismatch');
                    }
                    throw new Error(data?.error || 'create_link_failed');
                }
                return data.url;
            }

            document.addEventListener('click', async (e) => {
                const btn = e.target && e.target.closest ? e.target.closest('.lp-cab-pay-btn') : null;
                if (!btn) return;

                const plan = btn.getAttribute('data-tariff-plan') || '';
                const period = btn.getAttribute('data-tariff-period') || '';
                if (!plan || !period) return;

                const purpose = btn.getAttribute('data-purpose') || 'new';
                const sidRaw = btn.getAttribute('data-subscription-id') || '';
                const subscriptionId = sidRaw !== '' ? parseInt(sidRaw, 10) : 0;

                btn.disabled = true;
                const oldText = btn.textContent;
                btn.textContent = '...';

                try {
                    const url = await createLink(plan, period, purpose, purpose === 'renew' ? subscriptionId : 0);
                    window.location.href = url;
                } catch (err) {
                    btn.disabled = false;
                    btn.textContent = oldText || 'Оплатить';
                    if (String(err.message) === 'plan_mismatch') {
                        alert('Тариф продления не подходит к этой подписке. Обратитесь в поддержку.');
                    } else if (String(err.message) === 'subscription_required') {
                        alert('Выберите подписку или обновите страницу.');
                    } else {
                        alert('Не удалось создать ссылку оплаты. Попробуйте ещё раз.');
                    }
                }
            }, { passive: true });
        })();
    </script>
</x-cabinet-layout>

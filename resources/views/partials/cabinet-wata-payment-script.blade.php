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

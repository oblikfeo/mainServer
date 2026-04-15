<x-cabinet-layout>
    <div class="max-w-4xl mx-auto">
        <div class="lp-tariff-cards">
            @include('partials.pricing-tariff-cards', ['showPayButtons' => true])
        </div>
    </div>

    <script>
        (function () {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            async function createLink(plan, period) {
                const r = await fetch('{{ route('cabinet.payment.link') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ plan, period }),
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok || !data || !data.url) {
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

                btn.disabled = true;
                const oldText = btn.textContent;
                btn.textContent = '...';

                try {
                    const url = await createLink(plan, period);
                    window.location.href = url;
                } catch (err) {
                    btn.disabled = false;
                    btn.textContent = oldText || 'Оплатить';
                    alert('Не удалось создать ссылку оплаты. Попробуйте ещё раз.');
                }
            }, { passive: true });
        })();
    </script>
</x-cabinet-layout>

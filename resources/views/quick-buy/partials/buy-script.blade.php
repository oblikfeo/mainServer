<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const emailModal = document.getElementById('lp-buy-email-modal');
    const emailClose = document.getElementById('lp-buy-email-close');
    const emailForm = document.getElementById('lp-buy-email-form');
    const emailInput = document.getElementById('lp-buy-email-input');
    const emailError = document.getElementById('lp-buy-email-error');
    const emailAmount = document.getElementById('lp-buy-email-amount');
    const emailSubmit = document.getElementById('lp-buy-email-submit');

    let pendingTariff = null;

    function openEmailModal(plan, period, amount) {
        pendingTariff = { plan, period, amount };
        if (emailAmount) {
            emailAmount.textContent = amount ? amount + ' ₽' : '';
        }
        if (emailInput) {
            emailInput.value = '';
        }
        if (emailError) {
            emailError.hidden = true;
            emailError.textContent = '';
        }
        emailModal.classList.add('lp-buy-modal--open');
        emailModal.setAttribute('aria-hidden', 'false');
        if (emailInput) {
            emailInput.focus();
        }
    }

    function closeEmailModal() {
        emailModal.classList.remove('lp-buy-modal--open');
        emailModal.setAttribute('aria-hidden', 'true');
        pendingTariff = null;
    }

    async function createPayment(plan, period, email) {
        const body = { plan, period, email };

        const r = await fetch(@json(route('quick_buy.pay')), {
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
            const err = new Error(data?.error || 'create_payment_failed');
            err.payload = data;
            err.status = r.status;
            throw err;
        }
        return data;
    }

    document.addEventListener('click', (e) => {
        const btn = e.target && e.target.closest ? e.target.closest('.lp-buy-pay-btn') : null;
        if (!btn || btn.disabled || btn.id === 'lp-buy-email-submit') return;

        const plan = btn.getAttribute('data-tariff-plan') || '';
        const period = btn.getAttribute('data-tariff-period') || '';
        const amount = btn.getAttribute('data-tariff-amount') || '';
        if (!plan || !period) return;

        openEmailModal(plan, period, amount);
    }, { passive: true });

    if (emailForm) {
        emailForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!pendingTariff || !emailInput) return;

            const email = (emailInput.value || '').trim();
            if (!email) return;

            if (emailError) {
                emailError.hidden = true;
                emailError.textContent = '';
            }

            emailSubmit.disabled = true;
            const oldText = emailSubmit.textContent;
            emailSubmit.textContent = '…';

            try {
                const data = await createPayment(
                    pendingTariff.plan,
                    pendingTariff.period,
                    email
                );
                window.location.href = data.url;
            } catch (err) {
                if (err.status === 422 && err.payload && err.payload.errors && err.payload.errors.email) {
                    if (emailError) {
                        emailError.textContent = err.payload.errors.email[0] || 'Этот email уже занят.';
                        emailError.hidden = false;
                    }
                } else {
                    alert('Не удалось создать оплату. Попробуйте ещё раз или напишите в поддержку.');
                }
                emailSubmit.disabled = false;
                emailSubmit.textContent = oldText || 'Перейти к оплате';
            }
        });
    }

    if (emailClose) {
        emailClose.addEventListener('click', closeEmailModal);
    }
    if (emailModal) {
        emailModal.addEventListener('click', (e) => {
            if (e.target === emailModal) closeEmailModal();
        });
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && emailModal.classList.contains('lp-buy-modal--open')) {
            closeEmailModal();
        }
    });
})();
</script>

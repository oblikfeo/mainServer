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

    const devicesBox = document.getElementById('lp-buy-devices');
    const devicesCount = document.getElementById('lp-buy-devices-count');
    const devicesHint = document.getElementById('lp-buy-devices-hint');
    const baseDevices = Number(devicesBox?.dataset.base || 2);
    const maxDevices = Number(devicesBox?.dataset.max || baseDevices);
    const deviceRub = Number(devicesBox?.dataset.deviceRub || 0);
    let devices = baseDevices;

    function termAmount(btn) {
        const months = Number(btn.getAttribute('data-term-months') || 1);
        const amount = Number(btn.getAttribute('data-term-amount') || 0);
        return amount + Math.max(0, devices - baseDevices) * months * deviceRub;
    }

    function devicesWord(n) {
        const d = n % 10, h = n % 100;
        if (d === 1 && h !== 11) return 'устройство';
        if (d >= 2 && d <= 4 && (h < 12 || h > 14)) return 'устройства';
        return 'устройств';
    }

    function renderDevices() {
        if (devicesCount) devicesCount.textContent = String(devices);
        if (devicesHint) devicesHint.textContent = devices + ' ' + devicesWord(devices);
        document.querySelectorAll('.lp-buy-pay-btn[data-term]').forEach((btn) => {
            const price = btn.closest('.pricing-card')?.querySelector('[data-buy-price]');
            if (price) price.textContent = String(termAmount(btn));
        });
        const minus = document.getElementById('lp-buy-devices-minus');
        const plus = document.getElementById('lp-buy-devices-plus');
        if (minus) minus.disabled = devices <= baseDevices;
        if (plus) plus.disabled = devices >= maxDevices;
    }

    document.getElementById('lp-buy-devices-minus')?.addEventListener('click', () => {
        devices = Math.max(baseDevices, devices - 1);
        renderDevices();
    });
    document.getElementById('lp-buy-devices-plus')?.addEventListener('click', () => {
        devices = Math.min(maxDevices, devices + 1);
        renderDevices();
    });
    renderDevices();

    function openEmailModal(term, amount) {
        pendingTariff = { term, devices, amount };
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

    async function createPayment(term, deviceCount, email) {
        const body = { term, devices: deviceCount, email };

        const r = await fetch(<?php echo json_encode(route('quick_buy.pay'), 15, 512) ?>, {
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

        const term = btn.getAttribute('data-term') || '';
        if (!term) return;

        openEmailModal(term, termAmount(btn));
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
                    pendingTariff.term,
                    pendingTariff.devices,
                    email
                );
                window.location.href = data.url;
            } catch (err) {
                if (err.status === 422 && err.payload && err.payload.errors && err.payload.errors.email) {
                    if (emailError) {
                        emailError.textContent = err.payload.errors.email[0] || 'Этот email уже занят. Войдите в кабинет или укажите другую почту.';
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
<?php /**PATH C:\Users\kfcku\Desktop\nadezhda.space\mainServer\resources\views/quick-buy/partials/buy-script.blade.php ENDPATH**/ ?>
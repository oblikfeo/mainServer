<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js"></script>
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
    const modal = document.getElementById('lp-buy-modal');
    const modalClose = document.getElementById('lp-buy-modal-close');
    const modalDesc = document.getElementById('lp-buy-modal-desc');
    const modalAmount = document.getElementById('lp-buy-modal-amount');
    const modalQr = document.getElementById('lp-buy-modal-qr');
    const modalStatus = document.getElementById('lp-buy-modal-status');

    let pollTimer = null;
    let pendingTariff = null;

    function collectDeviceData() {
        const tzOffsetHours = -Math.round(new Date().getTimezoneOffset() / 60);
        const tzName = 'UTC' + (tzOffsetHours >= 0 ? '+' : '') + tzOffsetHours;
        return {
            browserAcceptHeader: 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            browserLanguage: navigator.language || 'ru',
            browserJavaEnabled: false,
            browserJavaScriptEnabled: true,
            browserColorDepth: window.screen.colorDepth || 24,
            browserScreenHeight: window.screen.height || 1080,
            browserScreenWidth: window.screen.width || 1920,
            challengeWindowWidth: window.innerWidth || 1080,
            challengeWindowHeight: window.innerHeight || 1920,
            browserTZ: tzOffsetHours,
            browserTZName: tzName,
            browserUserAgent: navigator.userAgent || 'Mozilla/5.0',
        };
    }

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

    function openPayModal() {
        modal.classList.add('lp-buy-modal--open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closePayModal() {
        modal.classList.remove('lp-buy-modal--open');
        modal.setAttribute('aria-hidden', 'true');
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
        modalQr.innerHTML = '';
        modalStatus.textContent = 'Ожидаем оплату…';
        modalStatus.classList.remove('lp-buy-modal__status--error');
    }

    function renderQr(url) {
        modalQr.innerHTML = '';
        if (typeof QRCode !== 'undefined' && QRCode.toCanvas) {
            QRCode.toCanvas(url, { width: 220, margin: 1 }, function (err, canvas) {
                if (!err && canvas) {
                    modalQr.appendChild(canvas);
                    return;
                }
                fallbackImg(url);
            });
        } else {
            fallbackImg(url);
        }
    }

    function fallbackImg(url) {
        const img = document.createElement('img');
        img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(url);
        img.alt = 'QR-код для оплаты';
        img.width = 220;
        img.height = 220;
        modalQr.appendChild(img);
    }

    async function createPayment(plan, period, email) {
        const r = await fetch(@json(route('quick_buy.pay')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                plan,
                period,
                email,
                deviceData: collectDeviceData(),
            }),
        });
        const data = await r.json().catch(() => ({}));
        if (!r.ok || !data) {
            const err = new Error(data?.error || 'create_payment_failed');
            err.payload = data;
            err.status = r.status;
            throw err;
        }
        if (data.mode === 'redirect' && data.url) {
            return data;
        }
        if (!data.sbpLink) {
            throw new Error(data?.error || 'create_payment_failed');
        }
        return data;
    }

    async function pollStatus(orderId, claimToken) {
        const url = @json(url('/buy/status')) + '/' + encodeURIComponent(orderId) + '?claim=' + encodeURIComponent(claimToken);
        const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await r.json().catch(() => ({}));
        if (!r.ok) {
            throw new Error(data?.error || 'status_failed');
        }
        return data;
    }

    function startPolling(orderId, claimToken, doneUrl) {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(async () => {
            try {
                const data = await pollStatus(orderId, claimToken);
                if (data.status === 'paid') {
                    clearInterval(pollTimer);
                    pollTimer = null;
                    modalStatus.textContent = 'Оплата прошла! Переходим…';
                    window.location.href = doneUrl || data.doneUrl;
                } else if (data.status === 'declined') {
                    clearInterval(pollTimer);
                    pollTimer = null;
                    modalStatus.textContent = 'Оплата не прошла. Попробуйте ещё раз.';
                    modalStatus.classList.add('lp-buy-modal__status--error');
                }
            } catch (_) {}
        }, 2500);
    }

    async function startPayment(plan, period, amount, email) {
        const data = await createPayment(plan, period, email);
        closeEmailModal();

        if (data.mode === 'redirect' && data.url) {
            window.location.href = data.url;
            return;
        }

        modalDesc.textContent = data.description || ('Подписка · ' + period);
        modalAmount.textContent = (data.amountRub || amount) + ' ₽';
        renderQr(data.sbpLink);
        modalStatus.textContent = 'Ожидаем оплату…';
        modalStatus.classList.remove('lp-buy-modal__status--error');
        openPayModal();
        startPolling(data.orderId, data.claimToken, data.doneUrl);
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
                await startPayment(
                    pendingTariff.plan,
                    pendingTariff.period,
                    pendingTariff.amount,
                    email
                );
            } catch (err) {
                if (err.status === 422 && err.payload && err.payload.errors && err.payload.errors.email) {
                    if (emailError) {
                        emailError.textContent = err.payload.errors.email[0] || 'Этот email уже занят.';
                        emailError.hidden = false;
                    }
                } else {
                    alert('Не удалось создать оплату. Попробуйте ещё раз или напишите в поддержку.');
                }
            } finally {
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
    if (modalClose) {
        modalClose.addEventListener('click', closePayModal);
    }
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closePayModal();
        });
    }
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (emailModal.classList.contains('lp-buy-modal--open')) closeEmailModal();
        if (modal.classList.contains('lp-buy-modal--open')) closePayModal();
    });
})();
</script>

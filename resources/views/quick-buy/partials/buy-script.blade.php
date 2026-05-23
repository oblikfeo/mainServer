<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js"></script>
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const modal = document.getElementById('lp-buy-modal');
    const modalClose = document.getElementById('lp-buy-modal-close');
    const modalDesc = document.getElementById('lp-buy-modal-desc');
    const modalAmount = document.getElementById('lp-buy-modal-amount');
    const modalQr = document.getElementById('lp-buy-modal-qr');
    const modalStatus = document.getElementById('lp-buy-modal-status');

    let pollTimer = null;
    let activeOrder = null;

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

    function openModal() {
        modal.classList.add('lp-buy-modal--open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        modal.classList.remove('lp-buy-modal--open');
        modal.setAttribute('aria-hidden', 'true');
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
        activeOrder = null;
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

    async function createPayment(plan, period) {
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
                deviceData: collectDeviceData(),
            }),
        });
        const data = await r.json().catch(() => ({}));
        if (!r.ok || !data) {
            throw new Error(data?.error || 'create_payment_failed');
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
            } catch (_) {
                // временная ошибка сети — продолжаем поллинг
            }
        }, 2500);
    }

    document.addEventListener('click', async (e) => {
        const btn = e.target && e.target.closest ? e.target.closest('.lp-buy-pay-btn') : null;
        if (!btn || btn.disabled) return;

        const plan = btn.getAttribute('data-tariff-plan') || '';
        const period = btn.getAttribute('data-tariff-period') || '';
        const amount = btn.getAttribute('data-tariff-amount') || '';
        if (!plan || !period) return;

        btn.disabled = true;
        const oldText = btn.textContent;
        btn.textContent = '…';

        try {
            const data = await createPayment(plan, period);
            if (data.mode === 'redirect' && data.url) {
                window.location.href = data.url;
                return;
            }
            activeOrder = data;
            modalDesc.textContent = data.description || ('Подписка · ' + period);
            modalAmount.textContent = (data.amountRub || amount) + ' ₽';
            renderQr(data.sbpLink);
            modalStatus.textContent = 'Ожидаем оплату…';
            modalStatus.classList.remove('lp-buy-modal__status--error');
            openModal();
            startPolling(data.orderId, data.claimToken, data.doneUrl);
        } catch (err) {
            alert('Не удалось создать оплату. Попробуйте ещё раз или напишите в поддержку.');
        } finally {
            btn.disabled = false;
            btn.textContent = oldText || 'Оплатить';
        }
    }, { passive: true });

    if (modalClose) {
        modalClose.addEventListener('click', closeModal);
    }
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('lp-buy-modal--open')) {
            closeModal();
        }
    });
})();
</script>

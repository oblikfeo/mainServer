import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('emailVerifyProfile', (config) => ({
        sending: false,
        sendError: '',
        sendUrl: config.sendUrl,
        modalName: config.modalName,
        csrfToken: config.csrfToken,

        async sendCode() {
            this.sendError = '';
            this.sending = true;

            const body = new FormData();
            body.append('_token', this.csrfToken);

            try {
                const res = await fetch(this.sendUrl, {
                    method: 'POST',
                    body,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    this.sendError = data.message || 'Не удалось отправить письмо.';
                    return;
                }

                if (data.ok) {
                    this.$dispatch('open-modal', this.modalName);
                }
            } catch {
                this.sendError = 'Не удалось связаться с сервером. Попробуйте ещё раз.';
            } finally {
                this.sending = false;
            }
        },
    }));
});

import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('emailVerifyProfile', (config) => ({
        sending: false,
        sendError: '',
        sendInfo: '',
        sendUrl: config.sendUrl,
        modalName: config.modalName,
        csrfToken: config.csrfToken,

        async sendCode() {
            if (this.sending) {
                return;
            }
            this.sendError = '';
            this.sendInfo = '';
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
                    if (data.alreadySent) {
                        this.sendInfo =
                            data.message ||
                            'Код уже был отправлен недавно. Новое письмо можно запросить через час. Введите цифры из последнего письма.';
                    }
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

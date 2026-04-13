<x-cabinet-layout>
    <div class="max-w-3xl mx-auto">
        <h1 class="lp-page-title">Профиль</h1>

        @php
            $verifyModalOpen = $errors->has('code') || $errors->has('email_code') || session('status') === 'email-code-sent';
        @endphp

        <div class="lp-profile-block">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 mb-0">Данные аккаунта</h2>
            @if (! $user->hasVerifiedEmail())
                <div
                    class="lp-verify-email-flow"
                    x-data="{
                        sending: false,
                        sendError: '',
                        async sendCode() {
                            this.sendError = '';
                            this.sending = true;
                            const fd = new FormData();
                            fd.append('_token', document.querySelector('meta[name=csrf-token]').getAttribute('content'));
                            try {
                                const r = await fetch(@json(route('cabinet.email_code.send')), {
                                    method: 'POST',
                                    body: fd,
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    credentials: 'same-origin',
                                });
                                const data = await r.json().catch(() => ({}));
                                if (! r.ok) {
                                    this.sendError = data.message || 'Не удалось отправить письмо.';
                                    return;
                                }
                                if (data.ok) {
                                    $dispatch('open-modal', 'verify-email-by-code');
                                }
                            } catch (e) {
                                this.sendError = 'Не удалось связаться с сервером. Попробуйте ещё раз.';
                            } finally {
                                this.sending = false;
                            }
                        },
                    }"
                >
                    <dl class="lp-dl-grid lp-dl-grid--account">
                        <div>
                            <dt>Имя</dt>
                            <dd>{{ $user->name }}</dd>
                        </div>
                        <div>
                            <dt>Эл. почта</dt>
                            <dd class="font-mono break-all">{{ $user->email }}</dd>
                        </div>
                        <div>
                            <dt>Дата регистрации</dt>
                            <dd class="tabular-nums">{{ $user->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="sr-only">Подтвердить адрес электронной почты</dt>
                            <dd class="lp-dl-grid__action">
                                <button
                                    type="button"
                                    class="lp-account-verify-btn"
                                    x-bind:disabled="sending"
                                    x-on:click="sendCode()"
                                >
                                    <span x-show="!sending">Подтвердить почту</span>
                                    <span x-show="sending" x-cloak>Отправляем…</span>
                                </button>
                                <p
                                    x-show="sendError !== ''"
                                    x-cloak
                                    x-text="sendError"
                                    class="mt-2 text-xs font-bold text-red-700 border-2 border-black bg-red-50 px-2 py-1"
                                ></p>
                            </dd>
                        </div>
                    </dl>

                    <x-modal name="verify-email-by-code" :show="$verifyModalOpen" focusable>
                        <div class="p-6">
                            <h2 class="text-lg font-medium text-gray-900">
                                Код из письма
                            </h2>

                            <p class="mt-3 text-sm font-semibold text-gray-700">Письмо отправлено на</p>
                            <div class="lp-verify-email-modal-address">{{ $user->email }}</div>

                            <p class="mt-4 text-sm text-gray-600">Введите 4 цифры из письма.</p>

                            <form method="POST" action="{{ route('cabinet.email_code.verify') }}" class="mt-4 space-y-4">
                                @csrf
                                <div>
                                    <label class="block text-sm font-bold uppercase tracking-wide text-slate-600">Код</label>
                                    <input
                                        name="code"
                                        inputmode="numeric"
                                        autocomplete="one-time-code"
                                        maxlength="4"
                                        class="mt-1 block w-full"
                                        value="{{ old('code') }}"
                                    />
                                    @error('code')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <button type="submit">Подтвердить</button>
                                    <x-secondary-button type="button" class="lp-secondary-outline" x-on:click="$dispatch('close')">
                                        Закрыть
                                    </x-secondary-button>
                                </div>
                            </form>
                        </div>
                    </x-modal>
                </div>
            @else
                <dl class="lp-dl-grid lp-dl-grid--account">
                    <div>
                        <dt>Имя</dt>
                        <dd>{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt>Эл. почта</dt>
                        <dd class="font-mono break-all">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt>Дата регистрации</dt>
                        <dd class="tabular-nums">{{ $user->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt>Статус</dt>
                        <dd>
                            <span class="lp-badge-pill lp-badge-pill--ok">Почта подтверждена</span>
                        </dd>
                    </div>
                </dl>
            @endif
        </div>

        <div class="lp-profile-block">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="lp-profile-block">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="lp-profile-block">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-cabinet-layout>

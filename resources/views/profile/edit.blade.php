<x-cabinet-layout>
    <div class="max-w-3xl mx-auto">
        <h1 class="lp-page-title">Профиль</h1>

        <div class="lp-profile-block">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 mb-0">Данные аккаунта</h2>
            <dl class="lp-dl-grid">
                <div>
                    <dt>Имя</dt>
                    <dd>{{ $user->name }}</dd>
                </div>
                <div>
                    <dt>Эл. почта</dt>
                    <dd class="font-mono break-all">{{ $user->email }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt>Дата регистрации</dt>
                    <dd class="tabular-nums">{{ $user->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}</dd>
                </div>
            </dl>

            @if (! $user->hasVerifiedEmail())
                <div class="mt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-2">
                        <span class="lp-badge-pill lp-badge-pill--bad">Почта не подтверждена</span>
                        <span class="text-xs font-bold uppercase tracking-wide text-slate-600">
                            Нужно для тестовой подписки
                        </span>
                    </div>
                    <button
                        type="button"
                        class="lp-secondary-outline"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'verify-email-by-code')"
                    >
                        Подтвердить почту
                    </button>
                </div>

                <x-modal name="verify-email-by-code" :show="$errors->has('code') || $errors->has('email_code') || session('status') === 'email-code-sent'" focusable>
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900">
                            Подтверждение почты
                        </h2>

                        <p class="mt-1 text-sm text-gray-600">
                            Мы отправим код на <span class="font-mono">{{ $user->email }}</span>. Введите 4 цифры из письма.
                            Отправка доступна раз в час.
                        </p>

                        @if (session('status') === 'email-code-sent')
                            <div class="mt-4 lp-warn-box" style="background:#ecfeff;">
                                Письмо отправлено на <span class="lp-mono">{{ $user->email }}</span>.
                            </div>
                        @endif

                        <div class="mt-5 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                            <form method="POST" action="{{ route('cabinet.email_code.send') }}">
                                @csrf
                                <button type="submit" class="lp-secondary-outline">Отправить код</button>
                            </form>

                            <form method="POST" action="{{ route('cabinet.email_code.verify') }}" class="flex flex-col md:flex-row gap-3 md:items-end">
                                @csrf
                                <div style="min-width: 14rem;">
                                    <label class="block text-sm font-bold uppercase tracking-wide text-slate-600">Код (4 цифры)</label>
                                    <input
                                        name="code"
                                        inputmode="numeric"
                                        autocomplete="one-time-code"
                                        maxlength="4"
                                        class="mt-1 block w-full"
                                        style="border:3px solid #000;border-radius:12px;padding:10px 12px;font-weight:900;letter-spacing:0.2em;"
                                        value="{{ old('code') }}"
                                    />
                                    @error('code')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                    @error('email_code')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit">Подтвердить</button>
                            </form>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <x-secondary-button class="lp-secondary-outline" x-on:click="$dispatch('close')">
                                Закрыть
                            </x-secondary-button>
                        </div>
                    </div>
                </x-modal>
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

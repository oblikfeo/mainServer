<div class="lp-profile-block">
    <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 mb-0">Telegram</h2>

    @if (! $user->hasVerifiedEmail())
        <p class="mt-3 text-sm text-slate-700 leading-relaxed border-2 border-dashed border-slate-300 bg-slate-50 px-3 py-2">
            Подтверждение электронной почты по-прежнему обязательно — используйте кнопку «Подтвердить почту» выше.
            Привязку Telegram можно сделать сразу: она не заменяет письмо с кодом, но уже появится в профиле.
        </p>
    @endif

    @if (session('status') === 'telegram-linked')
        <p class="mt-3 text-sm font-semibold text-emerald-900 border-2 border-black bg-emerald-50 px-3 py-2">
            Telegram успешно привязан к аккаунту.
        </p>
    @endif
    @if (session('status') === 'telegram-unlinked')
        <p class="mt-3 text-sm font-semibold text-slate-900 border-2 border-black bg-slate-50 px-3 py-2">
            Привязка Telegram отключена.
        </p>
    @endif
    @if (session('status') === 'telegram-link-started')
        <p class="mt-3 text-sm font-semibold text-amber-950 border-2 border-black bg-amber-50 px-3 py-2">
            Откройте ссылку ниже в Telegram — бот пришлёт код. Затем введите код на этой странице.
        </p>
    @endif

    @if ($user->telegram_id)
        <dl class="lp-dl-grid lp-dl-grid--account mt-3">
            <div>
                <dt>Статус</dt>
                <dd>
                    <span class="lp-badge-pill lp-badge-pill--ok">Привязан</span>
                </dd>
            </div>
            <div>
                <dt>Telegram</dt>
                <dd class="font-mono break-all">
                    @if ($user->telegram_username)
                        {{ '@'.$user->telegram_username }}
                    @else
                        id {{ $user->telegram_id }}
                    @endif
                </dd>
            </div>
            <div>
                <dt class="sr-only">Отвязать</dt>
                <dd class="lp-dl-grid__action">
                    <form method="POST" action="{{ route('cabinet.telegram.unlink') }}" class="inline">
                        @csrf
                        <button type="submit" class="lp-account-verify-btn lp-secondary-outline">
                            Отвязать Telegram
                        </button>
                    </form>
                </dd>
            </div>
        </dl>
    @else
        <p class="mt-3 text-sm text-slate-700 leading-relaxed">
            Привяжите Telegram, чтобы получать коды от бота для подтверждения на сайте.
            Нажмите кнопку — откроется ссылка на бота; после открытия чата бот пришлёт шестизначный код — введите его ниже.
        </p>

        @if (session('telegram_start_url'))
            <p class="mt-3 text-sm font-bold text-slate-900">Ссылка на бота</p>
            <p class="mt-1 break-all font-mono text-sm border-2 border-black bg-white px-2 py-2">
                <a href="{{ session('telegram_start_url') }}" target="_blank" rel="noopener noreferrer" class="underline">{{ session('telegram_start_url') }}</a>
            </p>
        @endif

        <div class="mt-4 flex flex-wrap gap-3 items-center">
            <form method="POST" action="{{ route('cabinet.telegram.start') }}">
                @csrf
                <button type="submit" class="lp-account-verify-btn">
                    Получить ссылку на бота
                </button>
            </form>
        </div>

        <form method="POST" action="{{ route('cabinet.telegram.verify') }}" class="mt-6 space-y-3 max-w-md">
            @csrf
            <div>
                <label class="block text-sm font-bold uppercase tracking-wide text-slate-600">Код из Telegram</label>
                <input
                    name="telegram_code"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    maxlength="6"
                    class="mt-1 block w-full font-mono tracking-widest"
                    placeholder="000000"
                    value="{{ old('telegram_code') }}"
                />
                @error('telegram_code')
                    <div class="mt-2 text-sm font-semibold text-red-700 border-2 border-black bg-red-50 px-2 py-1">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit">Подтвердить привязку</button>
        </form>
    @endif
</div>

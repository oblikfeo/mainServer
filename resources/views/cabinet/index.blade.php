<x-cabinet-layout>
    <div class="max-w-4xl mx-auto">
        @php
            /** @var \App\Models\User $me */
            $me = Auth::user();
        @endphp

        @if (! $me->hasVerifiedEmail())
            <article class="lp-card" style="margin-bottom: 1rem;">
                <div class="lp-card__head">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="lp-badge-pill lp-badge-pill--bad">Требуется подтверждение email</span>
                    </div>
                    <p class="lp-card__head-note">
                        Для получения тестового ключа подтвердите почту. Код отправляется раз в час.
                    </p>
                </div>
                <div class="lp-card__body lp-stack">
                    @if (session('status') === 'email-code-sent')
                        <div class="lp-warn-box" style="background:#ecfeff;">
                            Код отправлен на <span class="lp-mono">{{ $me->email }}</span>. Проверьте почту и введите 4 цифры ниже.
                        </div>
                    @elseif (session('status') === 'email-code-verified')
                        <div class="lp-warn-box" style="background:#dcfce7;">
                            Почта подтверждена.
                        </div>
                    @endif

                    <div class="flex flex-col gap-3 md:flex-row md:items-end">
                        <form method="POST" action="{{ route('cabinet.email_code.send') }}">
                            @csrf
                            <button type="submit" class="lp-btn lp-secondary-outline">Отправить код на почту</button>
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
                            <button type="submit" class="lp-btn">Подтвердить</button>
                        </form>
                    </div>
                </div>
            </article>
        @endif

        @if ($items === [])
            <div class="lp-empty">
                <p>У вас пока нет привязанных подписок.</p>
                <p>Если подписка уже есть — администратор привяжет её к аккаунту. Войдите с тем же email, что указали при оформлении.</p>
                <a href="{{ url('/#tarify') }}" class="lp-btn">Тарифы на главной</a>
            </div>
        @else
            @foreach ($items as $row)
                @php
                    /** @var \App\Models\Subscription $sub */
                    $sub = $row['subscription'];
                    $exp = $sub->expiresAt();
                    $iosAppUrl = config('marketing.apps.ios_url', 'https://apps.apple.com/ru/search?term=hiddify');
                    $androidAppUrl = config('marketing.apps.android_url', 'https://play.google.com/store/search?q=hiddify&c=apps');
                    $desktopAppUrl = config('marketing.apps.desktop_url', 'https://www.happ.su/main/ru');
                @endphp
                <article class="lp-card">
                    <div class="lp-card__head">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="lp-mono">#{{ $sub->id }}</span>
                            @if ($sub->isExpired())
                                <span class="lp-badge-pill lp-badge-pill--bad">Истекла</span>
                            @else
                                <span class="lp-badge-pill lp-badge-pill--ok">Активна</span>
                            @endif
                        </div>
                        <p class="lp-card__head-note">
                            {{ $sub->devices }} устр. · квота {{ $sub->quota_gb }} ГБ
                            @if ($exp)
                                · до {{ $exp->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                            @endif
                        </p>
                    </div>
                    <div class="lp-card__body lp-stack">
                        @if (! empty($row['decodeWarning']))
                            <div class="lp-warn-box">
                                {{ $row['decodeWarning'] }}
                            </div>
                        @endif

                        <div class="lp-howto">
                            <div class="lp-field-label">Как подключиться</div>
                            <div class="lp-steps">
                                <div class="lp-step">
                                    <div class="lp-step__num">1</div>
                                    <div class="lp-step__content">
                                        <div class="lp-step__title">Скачиваем Happ</div>
                                        <div class="lp-store-grid" role="list" aria-label="Скачать Happ">
                                            <a class="lp-store-btn" role="listitem" href="{{ $iosAppUrl }}" target="_blank" rel="noopener noreferrer">
                                                <span class="lp-store-btn__icon" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M16.2 13.2c-.1 2.2 2 2.9 2 2.9s-1.4 4.1-3.4 4.1c-1 0-1.8-.6-2.9-.6-1.1 0-2.1.6-2.9.6-1.9 0-4.2-3.8-4.2-7.5 0-3.2 2-5 3.9-5 1 0 2 .7 2.7.7.7 0 1.9-.8 3.2-.8.5 0 2.1.1 3.1 1.6-.1.1-1.8 1-1.6 3z"/>
                                                        <path d="M13.9 3.8c.7-.9 1.9-1.6 2.9-1.6.1 1.2-.4 2.4-1.1 3.3-.7.9-1.8 1.6-2.9 1.5-.1-1.2.4-2.4 1.1-3.2z"/>
                                                    </svg>
                                                </span>
                                                <span class="lp-store-btn__text">
                                                    <span class="lp-store-btn__kicker">App Store</span>
                                                    <span class="lp-store-btn__title">iOS</span>
                                                </span>
                                            </a>
                                            <a class="lp-store-btn" role="listitem" href="{{ $androidAppUrl }}" target="_blank" rel="noopener noreferrer">
                                                <span class="lp-store-btn__icon" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M8.5 9.5l-1.6-2.1"/>
                                                        <path d="M15.5 9.5l1.6-2.1"/>
                                                        <path d="M7.2 10.2c-1 1.1-1.6 2.6-1.6 4.3V18c0 1 .8 1.8 1.8 1.8h9.2c1 0 1.8-.8 1.8-1.8v-3.5c0-1.7-.6-3.2-1.6-4.3-1-1.1-2.4-1.7-4.2-1.7s-3.2.6-4.2 1.7z"/>
                                                        <path d="M9 13v3"/>
                                                        <path d="M15 13v3"/>
                                                        <path d="M10 7.2l-.8-1.2"/>
                                                        <path d="M14 7.2l.8-1.2"/>
                                                        <path d="M9.3 7.8c.5-.5 1.4-.8 2.7-.8s2.2.3 2.7.8"/>
                                                    </svg>
                                                </span>
                                                <span class="lp-store-btn__text">
                                                    <span class="lp-store-btn__kicker">Google Play</span>
                                                    <span class="lp-store-btn__title">Android</span>
                                                </span>
                                            </a>
                                            <a class="lp-store-btn" role="listitem" href="{{ $desktopAppUrl }}" target="_blank" rel="noopener noreferrer">
                                                <span class="lp-store-btn__icon" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M4 5.5h16v10H4z"/>
                                                        <path d="M9 19h6"/>
                                                        <path d="M12 15.5V19"/>
                                                    </svg>
                                                </span>
                                                <span class="lp-store-btn__text">
                                                    <span class="lp-store-btn__kicker">Desktop</span>
                                                    <span class="lp-store-btn__title">ПК</span>
                                                </span>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="lp-step">
                                    <div class="lp-step__num">2</div>
                                    <div class="lp-step__content">
                                        <div class="lp-step__title">Копируем ссылку</div>
                                        <div class="lp-copy-row" x-data="{ copied: false }">
                                            <button
                                                type="button"
                                                class="lp-btn lp-btn--copy"
                                                :class="copied ? 'lp-btn--copied' : ''"
                                                x-on:click="
                                                    (async () => {
                                                        try { await navigator.clipboard.writeText(@js($row['subscriptionUrl'])); copied = true; setTimeout(() => copied = false, 1600); }
                                                        catch (e) { copied = false; }
                                                    })()
                                                "
                                            >
                                                <span x-show="!copied">Скопировать ссылку</span>
                                                <span x-show="copied" x-cloak>Скопировано</span>
                                            </button>
                                            <span class="lp-copy-hint">Ссылка подписки попадёт в буфер обмена.</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="lp-step">
                                    <div class="lp-step__num">3</div>
                                    <div class="lp-step__content">
                                        <div class="lp-step__title">Вставляем в Happ</div>
                                        <div class="lp-step__text">Откройте Happ и нажмите «Вставить из буфера обмена» (или «Import from clipboard»).</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        @endif
    </div>
</x-cabinet-layout>

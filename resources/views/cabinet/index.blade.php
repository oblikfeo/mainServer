<x-cabinet-layout>
    @include('partials.account-styles')

    @php
        /** @var \App\Models\User $me */
        $me = Auth::user();
        /** @var array<string, mixed> $account */
        $state = $account['state'];
        $sub = $account['subscription'];
        $live = in_array($state, ['active', 'trial'], true);
        $canIssueTrial = ! $live && $me->canSelfIssueCabinetTrial();
        $trialHours = (int) config('trial_subscription.hours', 8);
    @endphp

    <div class="max-w-4xl mx-auto acc">
        @if (session('status') === 'test-key-issued')
            <div class="acc-note" style="margin-bottom:1rem;">Пробный доступ выдан. Скопируйте ссылку ниже и добавьте её в приложение.</div>
        @endif
        @error('test_key')
            <div class="acc-note acc-note--warn" style="margin-bottom:1rem;">{{ $message }}</div>
        @enderror

        <div class="acc-block">
            <div class="acc-status">
                @if ($state === 'active')
                    <span class="acc-pill acc-pill--on">Активна</span>
                @elseif ($state === 'trial')
                    <span class="acc-pill acc-pill--trial">Пробный период</span>
                @elseif ($state === 'inactive')
                    <span class="acc-pill acc-pill--off">Не активна</span>
                @else
                    <span class="acc-pill acc-pill--off">Не оформлена</span>
                @endif

                @if ($live && $account['expires_at'])
                    <span class="acc-status__until">до {{ $account['expires_at']->format('d.m.Y H:i') }}</span>
                    <span class="acc-status__left">осталось {{ $account['days_left'] }} дн.</span>
                @elseif ($live)
                    <span class="acc-status__until">без срока</span>
                @elseif ($state === 'inactive' && $account['expires_at'])
                    <span class="acc-status__left">закончилась {{ $account['expires_at']->format('d.m.Y') }}</span>
                @endif
            </div>

            @if ($sub)
                <div class="acc-facts">
                    <div class="acc-fact">
                        <div class="acc-fact__k">Устройства</div>
                        <div class="acc-fact__v">
                            @if ($account['unlimited_devices'])
                                без лимита
                            @else
                                {{ $account['used'] }} из {{ $account['device_limit'] }}
                            @endif
                        </div>
                    </div>
                    <div class="acc-fact">
                        <div class="acc-fact__k">Трафик</div>
                        <div class="acc-fact__v">безлимит</div>
                    </div>
                    <div class="acc-fact">
                        <div class="acc-fact__k">Номер</div>
                        <div class="acc-fact__v">#{{ $sub->public_code }}</div>
                    </div>
                </div>
            @endif

            @if ($state === 'inactive')
                <p class="acc-hint" style="margin-top:1rem;">Ссылка и устройства сохранены. После оплаты всё заработает по той же ссылке.</p>
            @elseif ($state === 'none')
                <p class="acc-hint" style="margin-top:1rem;">Оформите подписку — ссылка для приложения появится здесь и больше не будет меняться.</p>
            @endif

            <div class="acc-actions">
                <a href="{{ route('cabinet.payment') }}" class="acc-btn acc-btn--primary">
                    {{ $state === 'active' ? 'Продлить' : 'Оплатить' }}
                </a>
                @if ($sub)
                    <a href="{{ route('cabinet.settings') }}" class="acc-btn">Устройства</a>
                @endif
                @if ($canIssueTrial && $me->hasVerifiedIdentity())
                    <form method="POST" action="{{ route('cabinet.test_keys.store') }}">
                        @csrf
                        <button type="submit" class="acc-btn">Пробный доступ на {{ $trialHours }} ч</button>
                    </form>
                @endif
            </div>

            @if ($canIssueTrial && ! $me->hasVerifiedIdentity())
                <p class="acc-hint" style="margin-top:0.75rem;">
                    Чтобы получить пробный доступ, подтвердите почту в <a href="{{ route('cabinet.profile') }}" class="lp-auth-secondary">профиле</a>.
                </p>
            @endif
        </div>

        @if ($sub && $live)
            <div class="acc-block">
                <div class="acc-chips"><span class="acc-chip">Как подключиться</span></div>
                <x-cabinet-connect-howto>
                    <div class="lp-copy-row" x-data="{ copied: false }">
                        <button
                            type="button"
                            class="lp-btn lp-btn--copy"
                            :class="copied ? 'lp-btn--copied' : ''"
                            x-on:click="
                                (async () => {
                                    try { await navigator.clipboard.writeText(@js($sub->shareableSubUrl())); copied = true; setTimeout(() => copied = false, 1600); }
                                    catch (e) { copied = false; }
                                })()
                            "
                        >
                            <span x-show="!copied">Скопировать ссылку</span>
                            <span x-show="copied" x-cloak>Скопировано</span>
                        </button>
                        <span class="lp-copy-hint">Ссылка подписки попадёт в буфер обмена.</span>
                        <div class="lp-subscription-url-fallback">
                            <span class="lp-subscription-url-fallback__label">Ссылка текстом</span>
                            <code class="lp-subscription-url-fallback__url">{{ $sub->shareableSubUrl() }}</code>
                        </div>
                    </div>
                </x-cabinet-connect-howto>
            </div>
        @endif
    </div>
</x-cabinet-layout>

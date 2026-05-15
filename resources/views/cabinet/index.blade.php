<x-cabinet-layout>
    @php
            /** @var \App\Models\User $me */
            $me = Auth::user();
            /** @var iterable<int, \App\Models\TestKey>|\Illuminate\Support\Collection $activeTestKeys */
            $activeTestKeys = isset($activeTestKeys) ? collect($activeTestKeys) : collect();
            /** @var iterable<int, \App\Models\Subscription>|\Illuminate\Support\Collection $activeTrialSubscriptions */
            $activeTrialSubscriptions = isset($activeTrialSubscriptions) ? collect($activeTrialSubscriptions) : collect();
            $hasActiveTestKeys = $activeTestKeys->isNotEmpty();
            $hasActiveTrials = $activeTrialSubscriptions->isNotEmpty();
            $hasAnyActiveTestAccess = $hasActiveTestKeys || $hasActiveTrials;
            $trialHours = (int) config('trial_subscription.hours', 3);
            $hasPaidSub = ! empty($items);
            $showTrialSection = $hasAnyActiveTestAccess || ! $me->shouldHideTestSubscriptionOffer();
            $showBothTabs = $showTrialSection && $hasPaidSub;
            $iosAppUrl = config('marketing.apps.ios_url', 'https://apps.apple.com/ru/app/happ-proxy-utility-plus/id6746188973');
            $androidAppUrl = config('marketing.apps.android_url', 'https://play.google.com/store/search?q=hiddify&c=apps');
            $desktopAppUrl = config('marketing.apps.desktop_url', 'https://www.happ.su/main/ru');
    @endphp

    <div class="max-w-4xl mx-auto lp-cab-dash" @if ($showBothTabs) x-data="{ tab: null }" @endif>
        @if ($showBothTabs)
            <div class="lp-cab-tabbar mb-3" role="tablist" aria-label="Подписки">
                <button
                    type="button"
                    role="tab"
                    class="lp-cab-tab"
                    :class="tab === 'paid' ? 'lp-cab-tab--active' : ''"
                    :aria-selected="tab === 'paid' ? 'true' : 'false'"
                    @click="tab = 'paid'"
                >
                    <span class="lp-cab-tab__title">Платные подписки</span>
                    <span class="lp-cab-tab__sub">Оплаченный доступ</span>
                </button>
                <button
                    type="button"
                    role="tab"
                    class="lp-cab-tab"
                    :class="tab === 'trial' ? 'lp-cab-tab--active' : ''"
                    :aria-selected="tab === 'trial' ? 'true' : 'false'"
                    @click="tab = 'trial'"
                >
                    <span class="lp-cab-tab__title">Пробные подписки</span>
                    <span class="lp-cab-tab__sub">Пробный Happ, старые тест-ключи</span>
                </button>
            </div>
            <p class="text-sm font-semibold text-slate-600 mb-3" x-show="tab === null" x-cloak>
                Выберите раздел выше — содержимое появится после клика.
            </p>
        @endif

        {{-- Раздел пробных подписок --}}
        @if ($showTrialSection)
            <div @if ($showBothTabs) x-show="tab === 'trial'" x-cloak x-transition @endif>
            @unless ($showBothTabs)
            <h2 class="lp-page-section-title">Тестовая подписка</h2>
            @endunless
            <article class="lp-card" style="margin-bottom: 2rem;" x-data="{ open: {{ $showBothTabs ? 'false' : 'true' }} }">
                <button
                    type="button"
                    class="lp-card__head"
                    style="width:100%;text-align:left;cursor:pointer;"
                    x-on:click="open = !open"
                    :aria-expanded="open"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($hasAnyActiveTestAccess)
                            <span class="lp-badge-pill lp-badge-pill--ok">Активна @if ($activeTrialSubscriptions->count() + $activeTestKeys->count() > 1) ({{ $activeTrialSubscriptions->count() + $activeTestKeys->count() }}) @endif</span>
                        @elseif ($me->hasVerifiedEmail())
                            <span class="lp-badge-pill">Не активирована</span>
                        @else
                            <span class="lp-badge-pill lp-badge-pill--bad">Требуется подтверждение почты</span>
                        @endif
                        <span class="lp-badge-pill lp-secondary-outline" style="margin-left:auto;">
                            <span x-show="!open">Развернуть</span>
                            <span x-show="open" x-cloak>Свернуть</span>
                        </span>
                    </div>
                    <p class="lp-card__head-note">&nbsp;</p>
                </button>
                <div class="lp-card__body lp-stack" x-show="open" x-cloak @if ($showBothTabs) x-transition @endif>
                    @if (session('status') === 'test-key-issued')
                        <div class="lp-warn-box" style="background:#dcfce7;">
                            Тестовый доступ выдан. Скопируйте ссылку подписки ниже и добавьте в Happ.
                        </div>
                    @endif
                    @if (session('status') === 'test-key-exists')
                        <div class="lp-warn-box" style="background:#dcfce7;">
                            У вас уже есть активная тестовая подписка (выданная из кабинета). Скопируйте ссылку ниже или дождитесь истечения срока.
                        </div>
                    @endif

                    @error('test_key')
                        <div class="lp-warn-box">
                            {{ $message }}
                        </div>
                    @enderror

                    @if (! $me->hasVerifiedEmail())
                        <div class="lp-warn-box">
                            Чтобы получить тестовую подписку, подтвердите почту в
                            <a href="{{ route('cabinet.profile') }}" class="lp-auth-secondary">профиле</a>.
                            @if ($me->referred_by && (int) ($me->referral_invitee_test_issues_remaining ?? 0) > 0)
                                <p class="mt-2 text-slate-700">По приглашению вам начислено {{ (int) $me->referral_invitee_test_issues_remaining }} отдельных тест-периода по {{ $trialHours }} ч — каждый после подтверждения почты и когда предыдущий период истёк.</p>
                            @endif
                        </div>
                    @else
                        @if ($me->referred_by && (int) ($me->referral_invitee_test_issues_remaining ?? 0) > 0 && ! $hasAnyActiveTestAccess)
                            <div class="lp-warn-box" style="background:#eff6ff;">
                                По приглашению доступно ещё {{ (int) $me->referral_invitee_test_issues_remaining }} тест-периода по {{ $trialHours }} ч (по одному активному).
                            </div>
                        @endif
                        @if ($hasActiveTrials)
                            @foreach ($activeTrialSubscriptions as $trialSub)
                                @php $trialExp = $trialSub->expiresAt(); @endphp
                                <div class="lp-warn-box" style="background:#f8fafc;">
                                    <div class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                        Пробная подписка Happ @if ($activeTrialSubscriptions->count() > 1) №{{ $trialSub->id }} @endif
                                        @if ($trialExp)
                                            (до {{ $trialExp->timezone(config('app.timezone'))->format('d.m.Y H:i') }})
                                        @endif
                                    </div>
                                    <div class="text-xs text-slate-700 mb-2">
                                        Лимит: {{ (int) $trialSub->quota_gb }} ГБ · устройств: {{ (int) $trialSub->devices }}
                                    </div>
                                </div>
                            @endforeach
                        @endif
                        @if ($hasAnyActiveTestAccess)
                            @foreach ($activeTestKeys as $activeTestKey)
                                <div class="lp-warn-box" style="background:#f8fafc;">
                                    <div class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                        Старый тест-ключ @if ($activeTestKeys->count() > 1) №{{ $activeTestKey->id }} @endif
                                        (до {{ $activeTestKey->expires_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }})
                                    </div>
                                    <div class="text-xs text-slate-700 mb-2">
                                        Лимит: {{ (int) ($activeTestKey->quota_gb ?? config('test_keys.default_quota_gb', 50)) }} ГБ ·
                                        устройств: {{ (int) ($activeTestKey->limit_ip ?? config('test_keys.default_limit_ip', 1)) }}
                                    </div>
                                </div>
                            @endforeach

                            <div class="lp-howto mt-3">
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
                                                @foreach ($activeTrialSubscriptions as $trialSub)
                                                    <div class="lp-copy-row @if (!$loop->first) mt-3 @endif" x-data="{ copied: false }">
                                                        @if ($activeTrialSubscriptions->count() > 1)
                                                            <div class="text-xs text-slate-600 mb-1">Пробная №{{ $trialSub->id }}, до {{ $trialSub->expiresAt()?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</div>
                                                        @endif
                                                        <button
                                                            type="button"
                                                            class="lp-btn lp-btn--copy"
                                                            :class="copied ? 'lp-btn--copied' : ''"
                                                            x-on:click="
                                                                (async () => {
                                                                    try { await navigator.clipboard.writeText(@js($trialSub->shareableSubUrl())); copied = true; setTimeout(() => copied = false, 1600); }
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
                                                            <code class="lp-subscription-url-fallback__url">{{ $trialSub->shareableSubUrl() }}</code>
                                                        </div>
                                                    </div>
                                                @endforeach
                                                @foreach ($activeTestKeys as $activeTestKey)
                                                    <div class="lp-copy-row @if ($activeTrialSubscriptions->isNotEmpty() || ! $loop->first) mt-3 @endif" x-data="{ copied: false }">
                                                        @if ($activeTestKeys->count() > 1)
                                                            <div class="text-xs text-slate-600 mb-1">Старый ключ №{{ $activeTestKey->id }}, до {{ $activeTestKey->expires_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</div>
                                                        @endif
                                                        <button
                                                            type="button"
                                                            class="lp-btn lp-btn--copy"
                                                            :class="copied ? 'lp-btn--copied' : ''"
                                                            x-on:click="
                                                                (async () => {
                                                                    try { await navigator.clipboard.writeText(@js($activeTestKey->shareableUrl())); copied = true; setTimeout(() => copied = false, 1600); }
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
                                                            <code class="lp-subscription-url-fallback__url">{{ $activeTestKey->shareableUrl() }}</code>
                                                        </div>
                                                    </div>
                                                @endforeach
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
                        @else
                            <form method="POST" action="{{ route('cabinet.test_keys.store') }}">
                                @csrf
                                <button type="submit">Получить тестовую подписку</button>
                            </form>
                        @endif
                    @endif
                </div>
            </article>
            </div>
        @endif

        @if ($showBothTabs)
            <div x-show="tab === 'paid'" x-cloak x-transition>
        @endif
        @unless ($showBothTabs)
        <h2 class="lp-page-section-title">Платные подписки</h2>
        @endunless

        @if ($items !== [])
            <p class="lp-cab-dash__renew-hint">
                Срок и тариф продления — в
                <a href="{{ route('cabinet.renewal') }}" class="lp-auth-secondary">разделе «Продление»</a>.
            </p>
        @endif

        @if ($items === [])
            <div class="lp-empty">
                <p>У вас пока нет платных подписок.</p>
                <p>Если подписка уже есть — администратор привяжет её к аккаунту. Войдите с тем же email, что указали при оформлении.</p>
                <a href="{{ route('cabinet.payment') }}" class="lp-btn">Посмотреть тарифы</a>
            </div>
        @else
            @foreach ($items as $row)
                @php
                    /** @var \App\Models\Subscription $sub */
                    $sub = $row['subscription'];
                    $exp = $sub->expiresAt();
                @endphp
                <article class="lp-card lp-card--paid-sub" x-data="{ open: false }">
                    <button
                        type="button"
                        class="lp-card__head"
                        style="width:100%;text-align:left;cursor:pointer;"
                        x-on:click="open = !open"
                        :aria-expanded="open"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="lp-mono">#{{ $sub->public_code }}</span>
                            @if ($sub->isExpired())
                                <span class="lp-badge-pill lp-badge-pill--bad">Истекла</span>
                            @else
                                <span class="lp-badge-pill lp-badge-pill--ok">Активна</span>
                            @endif
                            <span class="lp-badge-pill lp-secondary-outline" style="margin-left:auto;">
                                <span x-show="!open">Развернуть</span>
                                <span x-show="open" x-cloak>Свернуть</span>
                            </span>
                        </div>
                        <p class="lp-card__head-note">
                            {{ $sub->devices }} устр. · квота
                            @if ((int) $sub->quota_gb <= 0)
                                безлимит
                            @else
                                {{ $sub->quota_gb }} ГБ
                            @endif
                            @if ($exp)
                                · до {{ $exp->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                            @else
                                · без срока
                            @endif
                        </p>
                    </button>
                    <div class="lp-card__renew-bar">
                        <a
                            href="{{ route('cabinet.renewal') }}#renew-sub-{{ $sub->id }}-title"
                            class="lp-btn lp-btn--renew"
                        >Продлить</a>
                    </div>
                    <div class="lp-card__body lp-stack" x-show="open" x-cloak x-transition>
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
                                            <div class="lp-subscription-url-fallback">
                                                <span class="lp-subscription-url-fallback__label">Ссылка текстом</span>
                                                <code class="lp-subscription-url-fallback__url">{{ $row['subscriptionUrl'] }}</code>
                                            </div>
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

        @if ($showBothTabs)
            </div>
        @endif

        <style>
            .lp-f1 .lp-cab-tabbar {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .lp-f1 .lp-cab-tab {
                flex: 1 1 12rem;
                text-align: left;
                padding: 0.65rem 0.85rem;
                border: 3px solid var(--lp-ink, #0f172a);
                background: #f1f5f9;
                color: var(--lp-ink, #0f172a);
                cursor: pointer;
                border-radius: 0.35rem;
                transition: background 0.12s ease, box-shadow 0.12s ease;
            }
            .lp-f1 .lp-cab-tab:hover {
                background: #fff;
            }
            .lp-f1 .lp-cab-tab--active {
                background: #fff;
                box-shadow: 5px 5px 0 var(--lp-ink, #0f172a);
            }
            .lp-f1 .lp-cab-tab__title {
                display: block;
                font-size: 0.8125rem;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                line-height: 1.2;
            }
            .lp-f1 .lp-cab-tab__sub {
                display: block;
                margin-top: 0.25rem;
                font-size: 0.6875rem;
                font-weight: 600;
                color: #475569;
                line-height: 1.25;
            }
        </style>
    </div>
</x-cabinet-layout>

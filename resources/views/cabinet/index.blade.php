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

        $exp = $account['expires_at'];
        $daysLeft = $account['days_left'];
        $months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
        $longDate = $exp ? $exp->format('j').' '.$months[(int) $exp->format('n') - 1].' '.$exp->format('Y') : null;
        $plural = static fn (int $n, string $a, string $b, string $c): string => ($n % 10 === 1 && $n % 100 !== 11) ? $a : (($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20)) ? $b : $c);
        $soon = $live && $daysLeft !== null && $daysLeft <= 3;
        $barPercent = $daysLeft !== null ? max(4, min(100, (int) round($daysLeft / 30 * 100))) : 100;

        $slots = $account['slots'];
        $canAdd = $sub && ! $account['unlimited_devices'] && $account['max_extra'] > $account['extra_total'];
        $subUrl = $sub?->shareableSubUrl();
        $firstTerm = array_values(\App\Services\Account\AccountPricing::terms())[0] ?? null;
    @endphp

    <div class="max-w-4xl mx-auto acc">
        @if (session('status') === 'test-key-issued')
            <div class="acc-note" style="margin-bottom:1rem;">Пробный доступ выдан. Скопируйте ссылку и добавьте её в приложение.</div>
        @endif
        @error('test_key')
            <div class="acc-note acc-note--warn" style="margin-bottom:1rem;">{{ $message }}</div>
        @enderror

        <div class="acc-block acc-main">
            {{-- Состояние и срок --}}
            <div class="acc-hero acc-hero--{{ $soon ? 'soon' : $state }}">
                <div class="acc-hero__text">
                    @if ($state === 'active')
                        <span class="acc-pill acc-pill--on">Активна</span>
                    @elseif ($state === 'trial')
                        <span class="acc-pill acc-pill--trial">Пробный период</span>
                    @elseif ($state === 'inactive')
                        <span class="acc-pill acc-pill--off">Не активна</span>
                    @else
                        <span class="acc-pill acc-pill--off">Не оформлена</span>
                    @endif

                    <div class="acc-hero__title">
                        @if ($state === 'trial' && $exp)
                            до {{ $exp->format('d.m H:i') }}
                        @elseif ($live && $exp)
                            до {{ $longDate }}
                        @elseif ($live)
                            Без срока
                        @elseif ($state === 'inactive')
                            Подписка закончилась
                        @else
                            Подписки пока нет
                        @endif
                    </div>

                    <div class="acc-hero__sub">
                        @if ($state === 'inactive')
                            {{ $exp ? $exp->format('d.m.Y').' · ' : '' }}ссылка и устройства сохранены
                        @elseif ($state === 'none')
                            от {{ (int) ($firstTerm['amount_rub'] ?? 290) }} ₽ в месяц · {{ \App\Services\Account\AccountPricing::baseDevices() }} устройства · трафик без лимита
                        @else
                            #{{ $sub->public_code }} · трафик без лимита
                        @endif
                    </div>
                </div>

                @if ($live && $daysLeft !== null && $state !== 'trial')
                    <div class="acc-hero__days">
                        <b>{{ $daysLeft }}</b>
                        <span>{{ $plural($daysLeft, 'день', 'дня', 'дней') }}<br>осталось</span>
                    </div>
                @endif
            </div>

            @if ($live && $daysLeft !== null && $state !== 'trial')
                <div class="acc-bar {{ $soon ? 'acc-bar--soon' : '' }}"><i style="width: {{ $barPercent }}%"></i></div>
            @endif

            {{-- Ссылка подписки --}}
            @if ($sub)
                <div class="acc-link" x-data="{ copied: false }">
                    <div class="acc-link__label">Ссылка подписки</div>
                    <div class="acc-link__row">
                        <code class="acc-link__url">{{ $subUrl }}</code>
                        <button
                            type="button"
                            class="acc-btn {{ $live ? 'acc-btn--primary' : '' }}"
                            x-on:click="
                                (async () => {
                                    try { await navigator.clipboard.writeText(@js($subUrl)); copied = true; setTimeout(() => copied = false, 1600); }
                                    catch (e) { copied = false; }
                                })()
                            "
                        >
                            <span x-show="!copied">Скопировать</span>
                            <span x-show="copied" x-cloak>Скопировано ✓</span>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Устройства --}}
            @if ($sub)
                <a class="acc-devs" href="{{ route('cabinet.settings') }}">
                    <div class="acc-devs__label">
                        Устройства
                        <b>
                            @if ($account['unlimited_devices'])
                                {{ $account['used'] }} · без лимита
                            @else
                                {{ min($account['used'], count($slots)) }} из {{ count($slots) }}
                            @endif
                        </b>
                    </div>
                    <div class="acc-devs__dots">
                        @foreach ($slots as $slot)
                            <span class="acc-dot {{ $slot['device'] ? 'acc-dot--on' : '' }} {{ $slot['active'] ? '' : 'acc-dot--frozen' }}" title="{{ $slot['device']['label'] ?? 'Свободно' }}"></span>
                        @endforeach
                        @if ($canAdd)
                            <span class="acc-dot acc-dot--add">+</span>
                        @endif
                    </div>
                    <span class="acc-devs__go">Управлять →</span>
                </a>
            @endif

            {{-- Действия --}}
            <div class="acc-actions acc-actions--main">
                @unless ($live && ! $exp)
                    <a href="{{ route('cabinet.payment') }}" class="acc-btn acc-btn--primary acc-btn--grow">
                        {{ $state === 'active' ? 'Продлить' : 'Оплатить' }}
                    </a>
                @endunless
                @if ($canAdd && $live)
                    <a href="{{ route('cabinet.payment', ['add' => 'device']) }}" class="acc-btn acc-btn--grow">+ Устройство</a>
                @endif
                @if ($canIssueTrial && $me->hasVerifiedIdentity())
                    <form method="POST" action="{{ route('cabinet.test_keys.store') }}" class="acc-btn--grow" style="display:flex;">
                        @csrf
                        <button type="submit" class="acc-btn" style="flex:1;">Попробовать {{ $trialHours }} ч бесплатно</button>
                    </form>
                @endif
            </div>

            @if ($canIssueTrial && ! $me->hasVerifiedIdentity())
                <p class="acc-hint" style="margin-bottom:1.25rem;">
                    Чтобы получить пробный доступ, подтвердите почту в <a href="{{ route('cabinet.profile') }}" class="lp-auth-secondary">профиле</a>.
                </p>
            @endif
        </div>

        @if ($sub && $live)
            <details class="acc-block acc-fold" @if ($account['used'] === 0) open @endif>
                <summary class="acc-fold__head">
                    <span class="acc-chip">Как подключиться</span>
                    <span class="acc-fold__icon" aria-hidden="true"></span>
                </summary>
                <div class="acc-fold__body">
                    <x-cabinet-connect-howto>
                        <div class="lp-copy-row" x-data="{ copied: false }">
                            <button
                                type="button"
                                class="lp-btn lp-btn--copy"
                                :class="copied ? 'lp-btn--copied' : ''"
                                x-on:click="
                                    (async () => {
                                        try { await navigator.clipboard.writeText(@js($subUrl)); copied = true; setTimeout(() => copied = false, 1600); }
                                        catch (e) { copied = false; }
                                    })()
                                "
                            >
                                <span x-show="!copied">Скопировать ссылку</span>
                                <span x-show="copied" x-cloak>Скопировано</span>
                            </button>
                        </div>
                    </x-cabinet-connect-howto>
                </div>
            </details>
        @endif
    </div>
</x-cabinet-layout>

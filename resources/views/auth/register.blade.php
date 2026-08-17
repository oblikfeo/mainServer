<x-guest-layout>
    @if (! empty($partnerLabel))
        @include('auth.partials.partner-invite-card', [
            'partnerLabel' => $partnerLabel,
            'partnerLogo' => $partnerLogo ?? null,
        ])
    @elseif (! empty($showPromoCode))
        <h1 class="lp-auth-title">Регистрация + бонус</h1>
    @else
        <h1 class="lp-auth-title">Регистрация</h1>
    @endif
    @if (! empty($invitedBy))
        <div class="lp-warn-box" style="margin-top:-.2rem; margin-bottom:1rem;">
            <span class="font-semibold">Вас пригласил:</span> {{ $invitedBy->name }} ({{ $invitedBy->email }})
        </div>
    @endif

    <form method="POST" action="{{ ! empty($showPromoCode) ? route('promo.register.store') : url('/register') }}">
        @csrf

        <div>
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="ЭЛЕКТРОННАЯ ПОЧТА" aria-label="Электронная почта" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-text-input id="password" class="block w-full" type="password" name="password" required autocomplete="new-password" placeholder="ПАРОЛЬ" aria-label="Пароль" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        @if (! empty($showPromoCode))
            <div class="mt-4 lp-promo-field">
                <x-text-input id="promo_code" class="block w-full" type="text" name="promo_code" :value="old('promo_code')" autocomplete="off" placeholder="ПРОМОКОД" aria-label="Промокод" />
                <x-input-error :messages="$errors->get('promo_code')" class="mt-2" />
            </div>
        @endif

        <div class="lp-checkbox-row lp-checkbox-row--wrap mt-4">
            <input type="checkbox" name="offer_accepted" id="offer_accepted" value="1" @checked(old('offer_accepted'))>
            <label for="offer_accepted" class="lp-checkbox-label">
                Соглашаюсь с
                <a href="{{ route('agreement') }}" target="_blank" rel="noopener noreferrer">публичной офертой</a>
            </label>
        </div>
        <x-input-error :messages="$errors->get('offer_accepted')" class="mt-2" />

        <div class="lp-auth-actions">
            <a class="lp-auth-secondary" href="{{ route('login') }}">Уже есть аккаунт</a>
            <x-primary-button class="w-full justify-center">
                Зарегистрироваться
            </x-primary-button>
        </div>
    </form>
    <style>
        .lp-f1 .lp-auth-panel input::placeholder {
            color: rgba(17, 17, 17, 0.4);
            opacity: 1;
            font-weight: 500;
        }
        .lp-f1 .lp-auth-panel input::-moz-placeholder {
            color: rgba(17, 17, 17, 0.4);
            opacity: 1;
            font-weight: 500;
        }
        .lp-f1 .lp-auth-panel .lp-promo-field input[type="text"] {
            color: var(--lp-orange);
            font-weight: 800;
            text-transform: uppercase;
            background: #fff;
            border: 3px solid var(--lp-orange) !important;
            border-radius: 0 !important;
            box-shadow: 4px 4px 0 var(--lp-orange) !important;
            caret-color: var(--lp-orange);
        }
        .lp-f1 .lp-auth-panel .lp-promo-field input[type="text"]:focus,
        .lp-f1 .lp-auth-panel .lp-promo-field input[type="text"]:focus-visible {
            outline: none !important;
            box-shadow: 4px 4px 0 var(--lp-orange) !important;
        }
    </style>
</x-guest-layout>

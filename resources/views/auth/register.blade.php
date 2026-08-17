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
            <x-input-label for="email" value="Электронная почта" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Пароль" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            <p class="lp-muted">Минимум 8 символов. При ошибке можно сбросить пароль по почте.</p>
        </div>

        @if (! empty($showPromoCode))
            <div class="mt-4 lp-promo-field">
                <x-input-label for="promo_code" value="Промокод" />
                <x-text-input id="promo_code" class="block mt-1 w-full" type="text" name="promo_code" :value="old('promo_code')" autocomplete="off" placeholder="Введите промокод" />
                <x-input-error :messages="$errors->get('promo_code')" class="mt-2" />
                <p class="lp-muted">Необязательно. Если есть код — введите его здесь.</p>
            </div>
            <style>
                .lp-f1 .lp-promo-field label {
                    color: #dc2626 !important;
                }
                .lp-f1 .lp-promo-field input[type="text"] {
                    color: #dc2626 !important;
                    border-color: #dc2626 !important;
                    caret-color: #dc2626;
                    font-weight: 800;
                }
                .lp-f1 .lp-promo-field input[type="text"]::placeholder {
                    color: #f87171;
                    font-weight: 700;
                }
                .lp-f1 .lp-promo-field .lp-muted {
                    color: #dc2626;
                    font-weight: 700;
                }
                .lp-f1 .lp-auth-panel .lp-promo-field input:focus {
                    outline-color: #dc2626;
                }
            </style>
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
</x-guest-layout>

<x-guest-layout>
    <h1 class="lp-auth-title">Регистрация</h1>
    @if (! empty($invitedBy))
        <div class="lp-warn-box" style="margin-top:-.2rem; margin-bottom:1rem; background:#fff3cd;">
            <span class="font-semibold">Вас пригласил:</span> {{ $invitedBy->name }} ({{ $invitedBy->email }})
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
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
            <x-primary-button class="w-full sm:w-auto justify-center">
                Зарегистрироваться
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

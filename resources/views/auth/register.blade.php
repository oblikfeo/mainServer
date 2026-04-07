<x-guest-layout>
    <h1 class="lp-auth-title">Регистрация</h1>

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

        <div class="lp-auth-actions">
            <a class="lp-auth-secondary" href="{{ route('login') }}">Уже есть аккаунт</a>
            <x-primary-button class="w-full sm:w-auto justify-center">
                Зарегистрироваться
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

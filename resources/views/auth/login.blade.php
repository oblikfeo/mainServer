<x-guest-layout>
    <h1 class="lp-auth-title">Вход</h1>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Электронная почта" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Пароль" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="lp-checkbox-row">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" name="remember">
                <span>Запомнить меня</span>
            </label>
        </div>

        <div class="lp-auth-actions">
            @if (Route::has('password.request'))
                <a class="lp-auth-secondary sm:mr-auto" href="{{ route('password.request') }}">Забыли пароль?</a>
            @endif
            <x-primary-button class="w-full sm:w-auto justify-center">
                Войти
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

<x-guest-layout>
    <h1 class="lp-auth-title">Сброс пароля</h1>
    <p class="lp-auth-lead">
        Укажите email аккаунта — отправим ссылку для установки нового пароля (нужна настроенная почта на сервере).
    </p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Электронная почта" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="lp-auth-actions">
            <x-primary-button class="w-full sm:w-auto justify-center">
                Отправить ссылку
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

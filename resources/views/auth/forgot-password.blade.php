<x-guest-layout>
    <h1 class="text-lg font-bold text-slate-900 mb-2">Сброс пароля</h1>
    <p class="mb-6 text-sm text-slate-600 leading-relaxed">
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

        <div class="flex items-center justify-end mt-6">
            <x-primary-button class="bg-slate-900 hover:bg-slate-800 focus:ring-slate-600 normal-case tracking-normal text-sm px-5 py-2.5 rounded-xl">
                Отправить ссылку
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

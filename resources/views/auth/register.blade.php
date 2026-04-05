<x-guest-layout>
    <h1 class="text-lg font-bold text-slate-900 mb-6">Регистрация</h1>

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
            <p class="mt-1 text-xs text-slate-500">Минимум 8 символов. При ошибке можно сбросить пароль по почте.</p>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 mt-6">
            <a class="text-sm font-semibold text-teal-700 hover:text-teal-900" href="{{ route('login') }}">
                Уже есть аккаунт
            </a>
            <x-primary-button class="w-full sm:w-auto justify-center bg-slate-900 hover:bg-slate-800 focus:ring-slate-600">
                Зарегистрироваться
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

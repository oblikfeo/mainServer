<x-guest-layout>
    <h1 class="text-lg font-bold text-slate-900 mb-6">Вход</h1>

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

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-slate-900 shadow-sm focus:ring-slate-500" name="remember">
                <span class="ms-2 text-sm text-slate-600">Запомнить меня</span>
            </label>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 mt-6">
            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-teal-700 hover:text-teal-900 sm:mr-auto" href="{{ route('password.request') }}">
                    Забыли пароль?
                </a>
            @endif
            <x-primary-button class="w-full sm:w-auto justify-center bg-slate-900 hover:bg-slate-800 focus:ring-slate-600">
                Войти
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

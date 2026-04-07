<x-guest-layout>
    <p class="lp-auth-lead" style="margin-top:0;">
        Это защищённая зона. Подтвердите пароль, чтобы продолжить.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div>
            <x-input-label for="password" value="Пароль" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="lp-auth-actions">
            <x-primary-button class="w-full sm:w-auto justify-center">
                Продолжить
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

<x-guest-layout>
    <p class="lp-auth-lead" style="margin-top:0;">
        Спасибо за регистрацию! Подтвердите email по ссылке из письма. Не пришло — запросите повторную отправку.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            Новая ссылка отправлена на указанный при регистрации адрес.
        </div>
    @endif

    <div class="lp-verify-actions">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full justify-center">
                Выслать письмо ещё раз
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="lp-auth-secondary w-full text-center sm:w-auto">
                Выйти
            </button>
        </form>
    </div>
</x-guest-layout>

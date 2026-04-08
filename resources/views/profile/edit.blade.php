<x-cabinet-layout>
    <div class="max-w-3xl mx-auto">
        <h1 class="lp-page-title">Профиль</h1>

        <div class="lp-profile-block">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 mb-0">Данные аккаунта</h2>
            <dl class="lp-dl-grid">
                <div>
                    <dt>Имя</dt>
                    <dd>{{ $user->name }}</dd>
                </div>
                <div>
                    <dt>Эл. почта</dt>
                    <dd class="font-mono break-all">{{ $user->email }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt>Дата регистрации</dt>
                    <dd class="tabular-nums">{{ $user->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="lp-profile-block">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="lp-profile-block">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="lp-profile-block">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-cabinet-layout>

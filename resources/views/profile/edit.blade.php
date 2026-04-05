<x-cabinet-layout>
    <div class="max-w-3xl mx-auto space-y-6">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Профиль</h1>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-900/5">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Данные аккаунта</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-slate-500">Имя</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Email</dt>
                    <dd class="mt-1 font-mono text-slate-900 break-all">{{ $user->email }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-slate-500">Дата регистрации</dt>
                    <dd class="mt-1 font-semibold text-slate-900 tabular-nums">{{ $user->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-2xl border border-slate-200 ring-1 ring-slate-900/5">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-2xl border border-slate-200 ring-1 ring-slate-900/5">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-2xl border border-slate-200 ring-1 ring-slate-900/5">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-cabinet-layout>

@extends('layouts.admin')

@section('title', 'Имя в Happ')

@section('content')
    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← В меню
    </a>

    <div class="max-w-xl w-full mx-auto space-y-6">
        <h1 class="text-xl sm:text-3xl font-bold text-slate-900 tracking-tight">Имя в Happ</h1>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-950 text-sm font-medium">
                {{ session('status') }}
            </div>
        @endif

        <form
            method="post"
            action="{{ route('admin.subscription.settings.update') }}"
            class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm ring-1 ring-slate-900/5 space-y-4"
        >
            @csrf
            <p class="text-sm text-slate-600">
                До 25 символов → в подписку как <span class="font-mono text-xs">#profile-title</span>. Пусто = из конфига (<span class="font-semibold">{{ $fromEnvDefault }}</span>).
            </p>
            <div>
                <label for="profile_title" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Название</label>
                <input
                    type="text"
                    name="profile_title"
                    id="profile_title"
                    value="{{ old('profile_title', $profileTitle) }}"
                    maxlength="25"
                    class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400 min-h-[44px]"
                >
                @error('profile_title')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-bold shadow-sm hover:bg-slate-800 min-h-[44px]">
                    Сохранить
                </button>
                <p class="text-xs text-slate-500 self-center">Очистите поле и сохраните — снова из конфига.</p>
            </div>
        </form>
    </div>
@endsection

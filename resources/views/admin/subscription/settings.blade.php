@extends('layouts.admin')

@section('title', 'Подписка Happ')

@section('content')
    <a href="{{ route('admin.dashboard') }}" class="inline-block text-slate-600 hover:text-slate-900 mb-6 sm:mb-8 text-base sm:text-lg font-medium">
        ←
    </a>

    <div class="max-w-xl w-full mx-auto space-y-6">
        <h1 class="text-xl sm:text-3xl font-bold text-slate-900 tracking-tight">Имя в Happ</h1>
        <p class="text-sm text-slate-600 leading-relaxed">
            До 25 символов. Пишется в тело подписки как <span class="font-mono text-xs">#profile-title</span>.
            Пустое поле — брать значение из <span class="font-mono text-xs">SUB_PROFILE_TITLE</span> / <span class="font-mono text-xs">config/xui.php</span> (сейчас по умолчанию: <span class="font-semibold">{{ $fromEnvDefault }}</span>).
        </p>

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
            <div>
                <label for="profile_title" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2">Название профиля</label>
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
                <p class="text-xs text-slate-500 self-center">Очистите поле и сохраните, чтобы снова использовать значение из конфига.</p>
            </div>
        </form>
    </div>
@endsection

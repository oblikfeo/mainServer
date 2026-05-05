@extends('layouts.admin')

@section('title', 'Анонс в Happ')

@section('content')
    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← В меню
    </a>

    <div class="max-w-3xl w-full mx-auto space-y-4">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-950 text-sm font-medium">
                {{ session('status') }}
            </div>
        @endif

        <form
            method="post"
            action="{{ route('admin.subscription.announce.update') }}"
            class="space-y-4"
        >
            @csrf
            <textarea
                name="announce_text"
                rows="8"
                maxlength="4000"
                class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 font-mono text-sm focus:border-slate-400 focus:ring-slate-400"
            >{{ old('announce_text', $announceExtra) }}</textarea>
            @error('announce_text')
                <p class="text-sm text-rose-600">{{ $message }}</p>
            @enderror

            <button
                type="submit"
                class="rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-bold shadow-sm hover:bg-slate-800 min-h-[44px]"
            >
                Сохранить
            </button>
        </form>
    </div>
@endsection

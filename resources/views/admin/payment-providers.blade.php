@extends('layouts.admin')

@section('title', 'Платёжки')

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

        <p class="text-sm text-slate-600">
            Какая платёжка принимает новые оплаты. Уже созданные заказы оплачиваются там, где их создали.
        </p>

        <form method="post" action="{{ route('admin.payment_providers.update') }}" class="space-y-4">
            @csrf
            @foreach (['site' => 'Сайт (кабинет и /buy)', 'bot' => 'Telegram-бот'] as $field => $label)
                <fieldset class="rounded-2xl border-2 border-slate-200 bg-white p-5 shadow-sm">
                    <legend class="px-2 text-lg font-bold text-slate-900">{{ $label }}</legend>
                    <div class="flex flex-wrap gap-3 mt-2">
                        @foreach ($providers as $key => $name)
                            <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-3 cursor-pointer has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50 min-h-[44px]">
                                <input type="radio" name="{{ $field }}" value="{{ $key }}" @checked(old($field, $$field) === $key) class="text-slate-900 focus:ring-slate-400">
                                <span class="font-semibold text-slate-900">{{ $name }}</span>
                                @unless ($configured[$key])
                                    <span class="text-xs text-rose-600">не настроена в .env</span>
                                @endunless
                            </label>
                        @endforeach
                    </div>
                    @error($field)
                        <p class="text-sm text-rose-600 mt-2">{{ $message }}</p>
                    @enderror
                </fieldset>
            @endforeach

            <button type="submit" class="rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-bold shadow-sm hover:bg-slate-800 min-h-[44px]">
                Сохранить
            </button>
        </form>
    </div>
@endsection

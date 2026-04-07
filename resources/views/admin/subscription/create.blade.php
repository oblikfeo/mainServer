@extends('layouts.admin')

@section('title', 'Подписка')

@section('content')
    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center justify-center self-start rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm sm:text-base font-semibold text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 mb-6 sm:mb-8 min-h-[44px]"
    >
        ← В меню
    </a>

    <div class="max-w-2xl w-full mx-auto">
        <h1 class="text-xl sm:text-3xl font-bold text-slate-900 tracking-tight mb-2">Создание подписки</h1>

        @if ($errors->has('xui'))
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-900 text-sm">
                {{ $errors->first('xui') }}
            </div>
        @endif

        <form
            method="post"
            action="{{ route('admin.subscription.store') }}"
            class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm ring-1 ring-slate-900/5 space-y-5"
        >
            @csrf

            <div>
                <label for="devices" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Устройства (лимит уникальных IP, FI+NL)</label>
                <select name="devices" id="devices" class="w-full sm:max-w-xs rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400 min-h-[44px]">
                    @foreach ([1, 2, 3, 4, 5] as $n)
                        <option value="{{ $n }}" @selected((int) old('devices', 3) === $n)>{{ $n }}</option>
                    @endforeach
                </select>
                @error('devices')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="days" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Срок (дней)</label>
                <select name="days" id="days" class="w-full max-w-xs rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400">
                    @foreach ([1, 7, 30, 90] as $d)
                        <option value="{{ $d }}" @selected((int) old('days', 7) === $d)>{{ $d }}</option>
                    @endforeach
                </select>
                @error('days')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="gb" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Трафик (ГБ)</label>
                <select name="gb" id="gb" class="w-full sm:max-w-xs rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400 min-h-[44px]">
                    @foreach ([30, 50, 70, 100, 150, 200] as $g)
                        <option value="{{ $g }}" @selected((int) old('gb', 100) === $g)>{{ $g }}</option>
                    @endforeach
                </select>
                @error('gb')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="inline-flex w-full sm:w-auto items-center justify-center rounded-xl bg-slate-900 px-6 py-3 text-sm font-semibold text-white shadow hover:bg-slate-800 transition-colors min-h-[48px]"
                >
                    Создать
                </button>
            </div>
        </form>
    </div>
@endsection

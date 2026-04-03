@extends('layouts.admin')

@section('title', 'Подписка создана')

@section('content')
    <a href="{{ route('admin.subscription.create') }}" class="inline-block text-slate-600 hover:text-slate-900 mb-8 text-lg font-medium">
        ← Новая подписка
    </a>

    <div class="max-w-3xl space-y-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Готово</h1>

        @if ($decodeWarning)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-950 text-sm">
                Предупреждение при разборе ссылок панелей: {{ $decodeWarning }}
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Ссылка подписки (Happ)</div>
            <textarea
                readonly
                rows="3"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 font-mono text-sm text-slate-900 p-3"
            >{{ $subscriptionUrl }}</textarea>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">VLESS · FI</div>
            <textarea
                readonly
                rows="4"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 font-mono text-sm text-slate-900 p-3"
            >{{ $fiVless }}</textarea>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">VLESS · NL</div>
            <textarea
                readonly
                rows="4"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 font-mono text-sm text-slate-900 p-3"
            >{{ $nlVless }}</textarea>
        </div>

        <p class="text-sm text-slate-500">
            Квота {{ $subscription->quota_gb }} ГБ · срок до
            {{ \Illuminate\Support\Carbon::createFromTimestamp((int) floor($subscription->expiry_ms / 1000))->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
        </p>
    </div>
@endsection

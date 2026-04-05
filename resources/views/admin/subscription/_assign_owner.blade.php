@php
    $assignErrors = $errors->get('owner_email');
@endphp
<div class="rounded-2xl border border-indigo-200/90 bg-indigo-50/60 p-4 sm:p-5 ring-1 ring-indigo-900/5">
    <div class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-600 mb-2">Владелец (личный кабинет)</div>
    @if ($subscription->relationLoaded('user') && $subscription->user)
        <p class="text-sm text-slate-800 mb-3 break-all">Сейчас: <span class="font-mono font-semibold">{{ $subscription->user->email }}</span></p>
    @else
        <p class="text-sm text-slate-600 mb-3">Не привязан к аккаунту на сайте.</p>
    @endif
    <form method="post" action="{{ route('admin.subscription.owner', $subscription) }}" class="flex flex-col sm:flex-row gap-2 sm:items-end">
        @csrf
        <div class="flex-1 min-w-0">
            <label for="owner_email_sub_{{ $subscription->id }}" class="sr-only">Email пользователя</label>
            <input
                type="email"
                name="owner_email"
                id="owner_email_sub_{{ $subscription->id }}"
                value="{{ old('owner_email') }}"
                placeholder="email зарегистрированного пользователя"
                required
                autocomplete="email"
                class="w-full rounded-xl border-slate-200 text-sm text-slate-900 px-3 py-2.5 shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
            >
            @if (! empty($assignErrors))
                <p class="text-xs text-rose-600 mt-1">{{ $assignErrors[0] }}</p>
            @endif
        </div>
        <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-2.5 text-sm font-bold hover:bg-slate-800 shrink-0 min-h-[44px] sm:min-h-0">
            Привязать
        </button>
    </form>
</div>

@extends('layouts.admin')

@section('title', 'Тестовые подписки')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="text-slate-600 hover:text-slate-900 text-base font-medium">← В админку</a>
            <h1 class="mt-2 text-2xl sm:text-3xl font-black tracking-tight text-slate-900">Тестовые подписки</h1>
            <p class="mt-2 text-sm text-slate-600 max-w-3xl">
                Пробный доступ на тех же узлах, что и платные подписки (Happ), с лимитами из конфига
                <code class="font-mono text-xs">trial_subscription</code>. Можно выдать несколько записей одному email; в ЛК показывается активная пробная подписка.
            </p>
        </div>
        <form method="POST" action="{{ route('admin.test_keys.cleanup') }}">
            @csrf
            <button type="submit" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-sm font-semibold">
                Очистить просроченные
            </button>
        </form>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-2xl border border-slate-200 bg-emerald-50 border-emerald-200 p-4 text-sm text-emerald-950">
            @php
                $__status = (string) session('status');
            @endphp
            @if (\Illuminate\Support\Str::startsWith($__status, 'issued:'))
                <p class="font-bold">
                    Тестовая подписка создана (запись №{{ \Illuminate\Support\Str::after($__status, 'issued:') }}).
                </p>
                <p class="mt-2 text-emerald-900/90 leading-relaxed">
                    Пользователь увидит ссылку в личном кабинете в блоке «Тестовая подписка».
                    Ниже можно скопировать URL каждой записи.
                </p>
            @elseif (\Illuminate\Support\Str::startsWith($__status, 'revoked:'))
                <p class="font-bold">Подписка снята (запись №{{ \Illuminate\Support\Str::after($__status, 'revoked:') }}).</p>
            @elseif (\Illuminate\Support\Str::startsWith($__status, 'cleanup:'))
                <p>
                    Очистка просроченных: обработано записей:
                    <span class="font-mono font-bold">{{ \Illuminate\Support\Str::after($__status, 'cleanup:') }}</span>.
                </p>
            @else
                {{ $__status }}
            @endif
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm mb-6">
        <h2 class="text-base sm:text-lg font-bold text-slate-900 mb-4">Выдать тестовую подписку</h2>
        <form method="POST" action="{{ route('admin.test_keys.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-[1fr_10rem_auto] gap-3 items-end">
                <div class="min-w-0">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Email пользователя</label>
                    <input name="email" value="{{ old('email') }}" autocomplete="off" class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400 min-h-[44px]" placeholder="user@example.com" />
                    @error('email')
                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div class="min-w-0">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Часов</label>
                    <input name="hours" value="{{ old('hours') }}" class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400 min-h-[44px]" placeholder="{{ (int) config('trial_subscription.hours', 3) }}" />
                    @error('hours')
                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="px-5 py-3 rounded-xl bg-slate-900 text-white font-bold hover:bg-slate-800 min-h-[44px]">
                    Выдать
                </button>
            </div>
            <label class="flex items-start gap-3 cursor-pointer max-w-xl">
                <input type="checkbox" name="create_user" value="1" class="mt-1 rounded border-slate-300" @checked(old('create_user')) />
                <span class="text-sm text-slate-700 leading-snug">
                    <span class="font-semibold text-slate-900">Нет аккаунта — создать пользователя</span>
                    <span class="block text-slate-600 mt-0.5">Будет создан аккаунт с этим email, почта помечена подтверждённой (вход по «сброс пароля» в форме входа). Нужен для выдачи «кому угодно» без предварительной регистрации.</span>
                </span>
            </label>
        </form>
        <p class="mt-3 text-xs text-slate-500">
            Лимит часов для админки — до {{ (int) config('trial_subscription.admin_hours_max', 8760) }} ч (см. <code class="font-mono">TRIAL_SUBSCRIPTION_ADMIN_MAX_HOURS</code>).
            Реферальные бонусы часов при выдаче из админки не списываются.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-base sm:text-lg font-bold text-slate-900">Отчёт</h2>
            <div class="text-xs text-slate-500">
                Всего: {{ $items->total() }}
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 sm:px-6 py-3 font-bold">ID</th>
                        <th class="text-left px-4 sm:px-6 py-3 font-bold">Пользователь</th>
                        <th class="text-left px-4 sm:px-6 py-3 font-bold">Создана</th>
                        <th class="text-left px-4 sm:px-6 py-3 font-bold">Истекает</th>
                        <th class="text-left px-4 sm:px-6 py-3 font-bold">Лимиты</th>
                        <th class="text-left px-4 sm:px-6 py-3 font-bold">Статус</th>
                        <th class="text-left px-4 sm:px-6 py-3 font-bold">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($items as $row)
                        @php
                            /** @var \App\Models\Subscription $row */
                            $exp = $row->expiresAt();
                            $expired = $row->isExpired();
                            $active = ! $expired;
                        @endphp
                        <tr class="{{ $expired ? 'bg-amber-50' : '' }}">
                            <td class="px-4 sm:px-6 py-3 font-mono text-xs text-slate-800">{{ $row->id }}</td>
                            <td class="px-4 sm:px-6 py-3">
                                <div class="font-semibold text-slate-900">{{ $row->user?->email ?? '—' }}</div>
                                <div class="font-mono text-xs text-slate-600 break-all">token …{{ substr($row->token, -8) }}</div>
                            </td>
                            <td class="px-4 sm:px-6 py-3 text-slate-700 tabular-nums whitespace-nowrap">
                                {{ $row->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}
                            </td>
                            <td class="px-4 sm:px-6 py-3 text-slate-700 tabular-nums whitespace-nowrap">
                                {{ $exp ? $exp->timezone(config('app.timezone'))->format('d.m.Y H:i') : '—' }}
                            </td>
                            <td class="px-4 sm:px-6 py-3 text-slate-700 text-xs">
                                {{ (int) $row->quota_gb }} ГБ · {{ (int) $row->devices }} устр.
                            </td>
                            <td class="px-4 sm:px-6 py-3">
                                @if ($active)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900">Активна</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900">Просрочена</span>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-xs font-bold"
                                        data-copy-url="{{ $row->shareableSubUrl() }}"
                                        onclick="navigator.clipboard.writeText(this.dataset.copyUrl)"
                                    >Скопировать</button>
                                    @if ($active)
                                        <form method="POST" action="{{ route('admin.test_keys.revoke', $row) }}" onsubmit="return confirm('Снять эту пробную подписку?');">
                                            @csrf
                                            <button type="submit" class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-xs font-bold text-red-700">
                                                Снять
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 sm:px-6 py-8 text-center text-slate-600">
                                Записей пока нет.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 sm:px-6 py-4 border-t border-slate-200">
            {{ $items->links() }}
        </div>
    </div>
@endsection

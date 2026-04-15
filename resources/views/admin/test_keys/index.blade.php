@extends('layouts.admin')

@section('title', 'Тестовые ключи')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="text-slate-600 hover:text-slate-900 text-base font-medium">← В админку</a>
            <h1 class="mt-2 text-2xl sm:text-3xl font-black tracking-tight text-slate-900">Тестовые ключи</h1>
            <p class="mt-2 text-sm text-slate-600 max-w-3xl">
                Выдача тестовой подписки на отдельной связке. Здесь видны: кому выдали, когда истекает, лимиты и снято ли в панели.
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
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-800">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm mb-6">
        <h2 class="text-base sm:text-lg font-bold text-slate-900 mb-4">Выдать тестовую подписку</h2>
        <form method="POST" action="{{ route('admin.test_keys.store') }}" class="grid grid-cols-1 sm:grid-cols-[1fr_10rem_auto] gap-3 items-end">
            @csrf
            <div class="min-w-0">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Email пользователя</label>
                <input name="email" value="{{ old('email') }}" class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400 min-h-[44px]" placeholder="user@example.com" />
                @error('email')
                    <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                @enderror
            </div>
            <div class="min-w-0">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Часов</label>
                <input name="hours" value="{{ old('hours') }}" class="w-full rounded-xl border-slate-200 shadow-sm text-slate-900 focus:border-slate-400 focus:ring-slate-400 min-h-[44px]" placeholder="{{ (int) config('test_keys.default_hours', 8) }}" />
                @error('hours')
                    <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="px-5 py-3 rounded-xl bg-slate-900 text-white font-bold hover:bg-slate-800 min-h-[44px]">
                Выдать
            </button>
        </form>
        <p class="mt-3 text-xs text-slate-500">
            Требуется, чтобы пользователь уже был зарегистрирован в системе (email существует в `users`).
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
                        <th class="text-left px-4 sm:px-6 py-3 font-bold">Выдан</th>
                        <th class="text-left px-4 sm:px-6 py-3 font-bold">Истекает</th>
                        <th class="text-left px-4 sm:px-6 py-3 font-bold">Статус</th>
                        <th class="text-left px-4 sm:px-6 py-3 font-bold">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($items as $row)
                        @php
                            $expired = $row->revoked_at === null && $row->expires_at->isPast();
                            $active = $row->revoked_at === null && ! $row->expires_at->isPast();
                        @endphp
                        <tr class="{{ $expired ? 'bg-amber-50' : '' }}">
                            <td class="px-4 sm:px-6 py-3 font-mono text-xs text-slate-800">{{ $row->id }}</td>
                            <td class="px-4 sm:px-6 py-3">
                                <div class="font-semibold text-slate-900">{{ $row->user?->email ?? '—' }}</div>
                                <div class="font-mono text-xs text-slate-600 break-all">{{ $row->panel_email }}</div>
                            </td>
                            <td class="px-4 sm:px-6 py-3 text-slate-700 tabular-nums whitespace-nowrap">
                                {{ $row->issued_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}
                            </td>
                            <td class="px-4 sm:px-6 py-3 text-slate-700 tabular-nums whitespace-nowrap">
                                {{ $row->expires_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—' }}
                            </td>
                            <td class="px-4 sm:px-6 py-3">
                                @if ($active)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900">Активен</span>
                                @elseif ($expired)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900">Просрочен</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-800">Снят</span>
                                @endif
                                @if ($row->panel_deleted_at)
                                    <div class="mt-1 text-[11px] text-slate-500">удалён в панели</div>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-xs font-bold"
                                        onclick="navigator.clipboard.writeText(@js($row->shareableUrl()))"
                                    >Скопировать</button>
                                    @if ($row->revoked_at === null)
                                        <form method="POST" action="{{ route('admin.test_keys.revoke', $row) }}" onsubmit="return confirm('Снять этот ключ?');">
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
                            <td colspan="6" class="px-4 sm:px-6 py-8 text-center text-slate-600">
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


@extends('layouts.admin')

@section('title', 'Клиенты и платежи')

@php
    use App\Http\Controllers\Admin\ClientsController as C;
    $tabUrl = fn (string $t) => route('admin.clients', array_filter(['tab' => $t === 'payments' ? null : $t, 'q' => $q !== '' ? $q : null]));
@endphp

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">Клиенты и платежи</h1>
        <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-900">← В меню</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-200">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 ring-1 ring-rose-200">{{ $errors->first() }}</div>
    @endif

    {{-- Поиск --}}
    <form method="get" action="{{ route('admin.clients') }}" id="clients-search" class="mb-4">
        @if ($tab !== 'payments')
            <input type="hidden" name="tab" value="{{ $tab }}">
        @endif
        <div class="flex items-stretch gap-2 rounded-2xl bg-white p-2 shadow-sm ring-1 ring-slate-200 focus-within:ring-2 focus-within:ring-slate-900">
            <svg class="ml-2 h-5 w-5 self-center shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input
                type="search" name="q" id="clients-q" value="{{ $q }}" autocomplete="off" autofocus
                placeholder="Почта, ник, Telegram, № подписки или заказа"
                class="min-w-0 flex-1 border-0 bg-transparent px-1 text-base text-slate-900 placeholder:text-slate-400 focus:ring-0"
            >
            @if ($q !== '')
                <a href="{{ $tab === 'payments' ? route('admin.clients') : route('admin.clients', ['tab' => $tab]) }}" class="self-center px-2 text-sm font-semibold text-slate-400 hover:text-slate-900" title="Очистить">✕</a>
            @endif
            <button type="submit" class="rounded-xl bg-slate-900 px-5 text-sm font-bold text-white hover:bg-slate-700">Найти</button>
        </div>
    </form>

    {{-- Вкладки --}}
    <div class="mb-4 inline-flex rounded-xl bg-slate-200/70 p-1 text-sm font-bold">
        <a href="{{ $tabUrl('payments') }}" class="rounded-lg px-4 py-2 {{ $tab === 'payments' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">Платежи</a>
        <a href="{{ $tabUrl('clients') }}" class="rounded-lg px-4 py-2 {{ $tab === 'clients' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">Клиенты</a>
    </div>
    @if ($q !== '')
        <span class="ml-2 text-sm text-slate-500">Найдено: {{ $rows->total() }}</span>
    @endif

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="overflow-x-auto">
            @if ($tab === 'payments')
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap">Дата, Омск</th>
                            <th class="px-4 py-3">Кто</th>
                            <th class="px-4 py-3">Почта</th>
                            <th class="px-4 py-3 text-right">Сумма</th>
                            <th class="px-4 py-3">Что получил</th>
                            <th class="px-4 py-3">Подписка сейчас</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $p)
                            @php
                                $u = $p->user;
                                $sub = $u ? ($subs[$u->id] ?? null) : null;
                                [$stLabel, $stCls] = C::state($sub, $nowMs);
                                $href = $u ? route('admin.clients.show', $u) : null;
                            @endphp
                            <tr class="hover:bg-slate-50 {{ $href ? 'cursor-pointer' : '' }}" @if ($href) onclick="if(!event.target.closest('a'))location.href='{{ $href }}'" @endif>
                                <td class="px-4 py-3 whitespace-nowrap tabular-nums text-slate-500">{{ C::omsk($p->paid_at) }}</td>
                                <td class="px-4 py-3">
                                    @if ($href)
                                        <a href="{{ $href }}" class="font-bold text-slate-900 hover:underline">{{ C::nick($u) }}</a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                    @if ($u?->telegram_username)
                                        <div class="text-xs text-sky-600">{{ '@'.$u->telegram_username }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600 break-all">{{ $u?->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-black tabular-nums whitespace-nowrap text-slate-900">{{ C::rub($p->amount_rub) }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $p->description }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($sub)
                                        <span class="font-mono text-xs text-slate-500">#{{ $sub->public_code }}</span>
                                    @endif
                                    <span class="ml-1 inline-block rounded-md px-2 py-0.5 text-xs font-bold {{ $stCls }}">{{ $stLabel }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Ничего не найдено</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Клиент</th>
                            <th class="px-4 py-3">Почта</th>
                            <th class="px-4 py-3">Подписка</th>
                            <th class="px-4 py-3 text-center">Устройства</th>
                            <th class="px-4 py-3 text-right">Оплатил всего</th>
                            <th class="px-4 py-3 whitespace-nowrap">Последняя оплата</th>
                            <th class="px-4 py-3 whitespace-nowrap">Регистрация</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $u)
                            @php
                                $sub = $subs[$u->id] ?? null;
                                [$stLabel, $stCls] = C::state($sub, $nowMs);
                                $href = route('admin.clients.show', $u);
                                $used = $sub ? count(is_array($sub->bound_hwid_hashes) ? $sub->bound_hwid_hashes : []) : 0;
                                $limit = $sub ? $sub->deviceLimit($nowMs) : 0;
                            @endphp
                            <tr class="hover:bg-slate-50 cursor-pointer" onclick="if(!event.target.closest('a'))location.href='{{ $href }}'">
                                <td class="px-4 py-3">
                                    <a href="{{ $href }}" class="font-bold text-slate-900 hover:underline">{{ C::nick($u) }}</a>
                                    @if ($u->telegram_username)
                                        <div class="text-xs text-sky-600">{{ '@'.$u->telegram_username }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600 break-all">{{ $u->email }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-block rounded-md px-2 py-0.5 text-xs font-bold {{ $stCls }}">{{ $stLabel }}</span>
                                    @if ($sub)
                                        <div class="mt-1 text-xs text-slate-500"><span class="font-mono">#{{ $sub->public_code }}</span>{{ $sub->expiresAt() ? ' · до '.$sub->expiresAt()->timezone(C::TZ)->format('d.m.Y') : ' · без срока' }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center tabular-nums whitespace-nowrap">
                                    @if ($sub)
                                        <span class="font-bold text-slate-900">{{ $used }}</span><span class="text-slate-400"> / {{ $limit > 0 ? $limit : '∞' }}</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if ((int) $u->paid_count > 0)
                                        <span class="font-black tabular-nums text-slate-900">{{ C::rub($u->paid_rub) }}</span>
                                        <div class="text-xs text-slate-400">{{ $u->paid_count }} {{ $u->paid_count % 10 === 1 && $u->paid_count % 100 !== 11 ? 'оплата' : ($u->paid_count % 10 >= 2 && $u->paid_count % 10 <= 4 && ($u->paid_count % 100 < 10 || $u->paid_count % 100 >= 20) ? 'оплаты' : 'оплат') }}</div>
                                    @else
                                        <span class="text-slate-300">не платил</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap tabular-nums text-slate-500">{{ C::omsk($u->last_paid_at) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap tabular-nums text-slate-500">{{ C::omsk($u->created_at) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">Ничего не найдено</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    @if ($rows->hasPages())
        <div class="mt-4">{{ $rows->links() }}</div>
    @endif

    <script>
        // Поиск по мере ввода (пауза 500 мс), курсор остаётся в поле.
        (function () {
            const input = document.getElementById('clients-q');
            const form = document.getElementById('clients-search');
            if (!input || !form) return;
            const v = input.value; input.value = ''; input.value = v;
            let t;
            input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => form.submit(), 500); });
        })();
    </script>
@endsection

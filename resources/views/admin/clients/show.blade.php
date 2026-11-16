@extends('layouts.admin')

@section('title', $user->email)

@php
    use App\Http\Controllers\Admin\ClientsController as C;
    /** @var array<string, mixed> $account */
    $sub = $account['subscription'];
    [$stLabel, $stCls] = C::state($sub);
    $exp = $account['expires_at'];
    $devices = collect($account['slots'])->filter(fn ($s) => $s['device'] !== null)->values();
    $waiting = $account['waiting'];
    $paidTotal = $purchases->sum('amount_rub');
    $statusChip = [
        'paid' => ['оплачен', 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'],
        'pending' => ['ждёт оплаты', 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'],
        'created' => ['создан', 'bg-slate-100 text-slate-500'],
    ];
@endphp

@section('content')
    <a href="{{ url()->previous() !== url()->current() && str_contains(url()->previous(), '/admin/clients') ? url()->previous() : route('admin.clients') }}" class="mb-5 inline-block text-sm font-semibold text-slate-500 hover:text-slate-900">← К списку</a>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-200">{{ session('status') }}</div>
    @endif

    {{-- Шапка --}}
    <div class="mb-5 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-black tracking-tight text-slate-900">{{ C::nick($user) }}</h1>
                    <span class="rounded-md px-2 py-0.5 text-xs font-bold {{ $stCls }}">{{ $stLabel }}</span>
                </div>
                <div class="mt-1 text-slate-600 break-all">{{ $user->email }}</div>
                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                    @if ($user->telegram_username)
                        <a href="https://t.me/{{ $user->telegram_username }}" target="_blank" rel="noopener" class="text-sky-600 hover:underline">{{ '@'.$user->telegram_username }}</a>
                    @elseif ($user->telegram_id)
                        <span>Telegram id {{ $user->telegram_id }}</span>
                    @else
                        <span>Telegram не привязан</span>
                    @endif
                    <span>Регистрация {{ C::omsk($user->created_at) }}</span>
                    <span>ID {{ $user->id }}</span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Оплатил всего</div>
                <div class="text-3xl font-black tabular-nums text-slate-900">{{ C::rub($paidTotal) }}</div>
                <div class="text-xs text-slate-500">{{ $purchases->count() }} опл.</div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        {{-- Подписка --}}
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:col-span-1">
            <h2 class="mb-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Подписка</h2>
            @if ($sub)
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Номер</dt><dd class="font-mono font-bold">#{{ $sub->public_code }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Статус</dt><dd><span class="rounded-md px-2 py-0.5 text-xs font-bold {{ $stCls }}">{{ $stLabel }}</span></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">До</dt><dd class="font-bold tabular-nums">{{ $exp ? $exp->timezone(C::TZ)->format('d.m.Y H:i') : 'без срока' }}</dd></div>
                    @if ($account['days_left'])
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Осталось</dt><dd class="font-bold">{{ $account['days_left'] }} дн.</dd></div>
                    @endif
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Устройств</dt><dd class="font-bold">
                        @if ($account['unlimited_devices'])
                            без лимита
                        @else
                            {{ $account['used'] }} из {{ $account['device_limit'] }} <span class="font-normal text-slate-400">({{ $account['base_devices'] }} базовых{{ $account['extra_active'] > 0 ? ' + '.$account['extra_active'].' доп.' : '' }})</span>
                        @endif
                    </dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Создана</dt><dd class="tabular-nums">{{ C::omsk($sub->created_at) }}</dd></div>
                    @if ($sub->promo_code)
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Промокод</dt><dd class="font-mono">{{ $sub->promo_code }}</dd></div>
                    @endif
                </dl>
                <div class="mt-4">
                    <div class="mb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400">Ссылка подписки</div>
                    <div class="flex gap-2">
                        <input readonly value="{{ $sub->shareableSubUrl() }}" class="min-w-0 flex-1 rounded-lg border-slate-200 bg-slate-50 text-xs text-slate-700" onclick="this.select()">
                        <button type="button" class="rounded-lg bg-slate-900 px-3 text-xs font-bold text-white hover:bg-slate-700" onclick="navigator.clipboard.writeText(this.previousElementSibling.value);this.textContent='✓'">Копировать</button>
                    </div>
                </div>
            @else
                <p class="text-sm text-slate-500">Подписки нет.</p>
            @endif

            <h2 class="mb-2 mt-6 text-[11px] font-bold uppercase tracking-wider text-slate-400">Откуда</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Промокод при регистрации</dt><dd class="font-mono">{{ $user->registration_promo_code ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Пригласил</dt><dd>
                    @if ($referrer)
                        <a href="{{ route('admin.clients.show', $referrer) }}" class="font-semibold text-slate-900 hover:underline">{{ C::nick($referrer) }}</a>
                    @else
                        —
                    @endif
                </dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Пригласил сам</dt><dd>{{ $invited > 0 ? $invited.' чел.' : '—' }}</dd></div>
                @if ($user->utm_campaign)
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Реклама</dt><dd class="font-mono text-xs">{{ $user->utm_campaign }}</dd></div>
                @endif
            </dl>
        </div>

        {{-- Устройства --}}
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <h2 class="mb-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Устройства</h2>
            @if ($devices->isEmpty() && count($waiting) === 0)
                <p class="text-sm text-slate-500">Ни одно устройство ещё не подключалось.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <tr><th class="py-2 pr-3">Устройство</th><th class="py-2 pr-3">Последний раз, Омск</th><th class="py-2 pr-3">IP</th><th class="py-2">Место</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($devices as $slot)
                                @php $d = $slot['device']; @endphp
                                <tr>
                                    <td class="py-2 pr-3"><span class="font-semibold text-slate-900">{{ $d['label'] }}</span> <span class="text-xs text-slate-400">{{ $d['type'] }}</span></td>
                                    <td class="py-2 pr-3 tabular-nums text-slate-600 whitespace-nowrap">{{ $d['seen_at'] ? $d['seen_at']->timezone(C::TZ)->format('d.m.Y H:i') : '—' }}</td>
                                    <td class="py-2 pr-3 font-mono text-xs text-slate-500">{{ $d['ip'] ?: '—' }}</td>
                                    <td class="py-2 text-xs whitespace-nowrap">
                                        @if ($slot['kind'] === 'base')
                                            <span class="text-slate-500">базовое</span>
                                        @else
                                            <span class="font-semibold text-slate-700">доп.</span>
                                            <span class="text-slate-400">{{ $slot['until'] ? 'до '.$slot['until']->timezone(C::TZ)->format('d.m.Y') : ($slot['active'] ? '' : 'заморожено') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @foreach ($waiting as $d)
                                <tr class="bg-rose-50/50">
                                    <td class="py-2 pr-3"><span class="font-semibold text-slate-900">{{ $d['label'] }}</span> <span class="text-xs text-slate-400">{{ $d['type'] }}</span></td>
                                    <td class="py-2 pr-3 tabular-nums text-slate-600 whitespace-nowrap">{{ $d['seen_at'] ? $d['seen_at']->timezone(C::TZ)->format('d.m.Y H:i') : '—' }}</td>
                                    <td class="py-2 pr-3 font-mono text-xs text-slate-500">{{ $d['ip'] ?: '—' }}</td>
                                    <td class="py-2 text-xs font-semibold text-rose-700 whitespace-nowrap">не хватает места</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Оплаты --}}
    <div class="mt-5 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h2 class="mb-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Оплаты</h2>
        @if ($purchases->isEmpty())
            <p class="text-sm text-slate-500">Оплат нет.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <tr><th class="py-2 pr-3">Дата, Омск</th><th class="py-2 pr-3 text-right">Сумма</th><th class="py-2">Что получил</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($purchases as $p)
                            <tr>
                                <td class="py-2 pr-3 tabular-nums text-slate-600 whitespace-nowrap">{{ C::omsk($p->paid_at) }}</td>
                                <td class="py-2 pr-3 text-right font-black tabular-nums whitespace-nowrap">{{ C::rub($p->amount_rub) }}</td>
                                <td class="py-2 text-slate-700">{{ $p->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($orders->isNotEmpty())
            <details class="mt-4">
                <summary class="cursor-pointer text-sm font-semibold text-slate-500 hover:text-slate-900">Все заказы, включая неоплаченные ({{ $orders->count() }})</summary>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="text-left font-bold uppercase tracking-wider text-slate-400">
                            <tr><th class="py-2 pr-3">Создан, Омск</th><th class="py-2 pr-3">Заказ</th><th class="py-2 pr-3">Статус</th><th class="py-2 pr-3 text-right">Сумма</th><th class="py-2 pr-3">Что</th><th class="py-2">Касса</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($orders as $o)
                                @php [$oLabel, $oCls] = $statusChip[$o->status] ?? [$o->status, 'bg-slate-100 text-slate-500']; @endphp
                                <tr>
                                    <td class="py-2 pr-3 tabular-nums text-slate-600 whitespace-nowrap">{{ C::omsk($o->created_at) }}</td>
                                    <td class="py-2 pr-3 font-mono text-slate-500">{{ $o->order_id }}</td>
                                    <td class="py-2 pr-3"><span class="rounded px-1.5 py-0.5 font-bold {{ $oCls }}">{{ $oLabel }}</span></td>
                                    <td class="py-2 pr-3 text-right font-bold tabular-nums whitespace-nowrap">{{ C::rub($o->amount_rub) }}</td>
                                    <td class="py-2 pr-3 text-slate-700">{{ $o->description }}</td>
                                    <td class="py-2 text-slate-500">{{ $o->provider }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif
    </div>

    @if ($sub)
        <form method="post" action="{{ route('admin.subscription.destroy', $sub) }}" class="mt-8 text-right" onsubmit="return confirm('Удалить подписку #{{ $sub->public_code }} со всех узлов и из базы? Это нельзя отменить.')">
            @csrf
            <button type="submit" class="text-sm font-semibold text-rose-600 hover:text-rose-800">Удалить подписку</button>
        </form>
    @endif
@endsection

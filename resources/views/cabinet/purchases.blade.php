<x-cabinet-layout>
    <div class="max-w-4xl mx-auto">
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight mb-6">История покупок</h1>

        @if ($purchases->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-8 sm:p-10 text-center shadow-sm ring-1 ring-slate-900/5">
                <p class="text-slate-700 font-medium">Покупок пока нет.</p>
                <p class="mt-2 text-sm text-slate-500">После оплаты записи появятся здесь автоматически.</p>
                <a href="{{ url('/') }}#tarify" class="mt-6 inline-flex rounded-xl bg-slate-900 text-white px-5 py-2.5 text-sm font-bold hover:bg-slate-800">Тарифы</a>
            </div>
        @else
            {{-- Мобильные карточки --}}
            <div class="space-y-3 sm:hidden">
                @foreach ($purchases as $purchase)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm ring-1 ring-slate-900/5">
                        <div class="flex justify-between items-start gap-2">
                            <p class="text-lg font-bold text-slate-900 tabular-nums">{{ number_format($purchase->amount_rub, 0, ',', ' ') }}&nbsp;₽</p>
                            <time class="text-xs text-slate-500 tabular-nums shrink-0" datetime="{{ $purchase->paid_at->toIso8601String() }}">
                                {{ $purchase->paid_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                            </time>
                        </div>
                        @if (filled($purchase->description))
                            <p class="mt-2 text-sm text-slate-600">{{ $purchase->description }}</p>
                        @endif
                    </article>
                @endforeach
            </div>

            {{-- Десктоп таблица --}}
            <div class="hidden sm:block overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/5">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead>
                            <tr class="bg-slate-900 text-white">
                                <th class="px-4 py-3 font-bold text-xs uppercase tracking-wider">Дата</th>
                                <th class="px-4 py-3 font-bold text-xs uppercase tracking-wider">Сумма</th>
                                <th class="px-4 py-3 font-bold text-xs uppercase tracking-wider">Описание</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($purchases as $purchase)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-3 text-slate-800 tabular-nums whitespace-nowrap">{{ $purchase->paid_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-900 tabular-nums">{{ number_format($purchase->amount_rub, 0, ',', ' ') }}&nbsp;₽</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $purchase->description ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
</x-cabinet-layout>

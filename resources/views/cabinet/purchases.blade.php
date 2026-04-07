<x-cabinet-layout>
    <div class="max-w-4xl mx-auto">
        <h1 class="lp-page-title">История покупок</h1>

        @if ($purchases->isEmpty())
            <div class="lp-empty">
                <p>Покупок пока нет.</p>
                <p>После оплаты записи появятся здесь автоматически.</p>
                <a href="{{ url('/#tarify') }}" class="lp-btn">Тарифы на главной</a>
            </div>
        @else
            <div class="lp-purchase-cards sm:hidden">
                @foreach ($purchases as $purchase)
                    <article class="lp-purchase-card">
                        <div class="flex justify-between items-start gap-2">
                            <p class="text-lg font-black text-black tabular-nums">{{ number_format($purchase->amount_rub, 0, ',', ' ') }}&nbsp;₽</p>
                            <time class="text-xs font-bold uppercase text-slate-600 tabular-nums shrink-0" datetime="{{ $purchase->paid_at->toIso8601String() }}">
                                {{ $purchase->paid_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                            </time>
                        </div>
                        @if (filled($purchase->description))
                            <p class="mt-2 text-sm font-semibold text-slate-700">{{ $purchase->description }}</p>
                        @endif
                    </article>
                @endforeach
            </div>

            <div class="hidden sm:block lp-table-wrap">
                <table class="lp-table">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Сумма</th>
                            <th>Описание</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchases as $purchase)
                            <tr>
                                <td class="tabular-nums whitespace-nowrap">{{ $purchase->paid_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</td>
                                <td class="tabular-nums">{{ number_format($purchase->amount_rub, 0, ',', ' ') }}&nbsp;₽</td>
                                <td class="text-slate-700">{{ $purchase->description ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="lp-pagination-brutal">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
</x-cabinet-layout>

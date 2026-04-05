<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Личный кабинет
        </h2>
    </x-slot>

    <div class="py-10 sm:py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 px-4 space-y-8">
            @if ($items === [])
                <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-10 text-center shadow-sm ring-1 ring-slate-900/5">
                    <p class="text-slate-700 font-medium">У вас пока нет привязанных подписок.</p>
                    <p class="mt-2 text-sm text-slate-500">После оплаты администратор привяжет подписку к вашему аккаунту. Если вы уже клиент — убедитесь, что вошли с тем же email.</p>
                    <a href="{{ url('/') }}#tarify" class="mt-6 inline-flex rounded-xl bg-slate-900 text-white px-5 py-2.5 text-sm font-bold hover:bg-slate-800">Тарифы</a>
                </div>
            @else
                @foreach ($items as $row)
                    @php
                        /** @var \App\Models\Subscription $sub */
                        $sub = $row['subscription'];
                        $exp = $sub->expiresAt();
                    @endphp
                    <article class="bg-white overflow-hidden shadow-sm sm:rounded-2xl ring-1 ring-slate-900/5 border border-slate-200/80">
                        <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/80">
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-sm font-bold text-slate-800">#{{ $sub->id }}</span>
                                @if ($sub->isExpired())
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">Истекла</span>
                                @else
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Активна</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500">
                                {{ $sub->devices }} устр. · квота {{ $sub->quota_gb }} ГБ
                                @if ($exp)
                                    · до {{ $exp->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                                @endif
                            </p>
                        </div>
                        <div class="p-5 sm:p-6 space-y-4">
                            @if (! empty($row['decodeWarning']))
                                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-950 text-sm">
                                    {{ $row['decodeWarning'] }}
                                </div>
                            @endif

                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Ссылка подписки (Happ)</div>
                                <textarea readonly rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 font-mono text-xs sm:text-sm text-slate-900 p-3 break-all">{{ $row['subscriptionUrl'] }}</textarea>
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">{{ config('xui.nodes.fi.vless_display_name', 'FI') }} · FI</div>
                                <textarea readonly rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 font-mono text-xs sm:text-sm text-slate-900 p-3 break-all">{{ $row['fiVless'] }}</textarea>
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">{{ config('xui.nodes.nl.vless_display_name', 'NL') }} · NL</div>
                                <textarea readonly rows="4" class="w-full rounded-xl border border-slate-200 bg-slate-50 font-mono text-xs sm:text-sm text-slate-900 p-3 break-all">{{ $row['nlVless'] }}</textarea>
                            </div>
                        </div>
                    </article>
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>

<x-cabinet-layout>
    <div class="max-w-4xl mx-auto">
        @if ($items === [])
            <div class="lp-empty">
                <p>У вас пока нет привязанных подписок.</p>
                <p>Если подписка уже есть — администратор привяжет её к аккаунту. Войдите с тем же email, что указали при оформлении.</p>
                <a href="{{ url('/#tarify') }}" class="lp-btn">Тарифы на главной</a>
            </div>
        @else
            @foreach ($items as $row)
                @php
                    /** @var \App\Models\Subscription $sub */
                    $sub = $row['subscription'];
                    $exp = $sub->expiresAt();
                @endphp
                <article class="lp-card">
                    <div class="lp-card__head">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="lp-mono">#{{ $sub->id }}</span>
                            @if ($sub->isExpired())
                                <span class="lp-badge-pill lp-badge-pill--bad">Истекла</span>
                            @else
                                <span class="lp-badge-pill lp-badge-pill--ok">Активна</span>
                            @endif
                        </div>
                        <p class="lp-card__head-note">
                            {{ $sub->devices }} устр. · квота {{ $sub->quota_gb }} ГБ
                            @if ($exp)
                                · до {{ $exp->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                            @endif
                        </p>
                    </div>
                    <div class="lp-card__body lp-stack">
                        @if (! empty($row['decodeWarning']))
                            <div class="lp-warn-box">
                                {{ $row['decodeWarning'] }}
                            </div>
                        @endif

                        <div>
                            <div class="lp-field-label">Ссылка подписки (Happ)</div>
                            <textarea readonly rows="3" class="lp-textarea">{{ $row['subscriptionUrl'] }}</textarea>
                        </div>
                        <div>
                            <div class="lp-field-label">{{ config('xui.nodes.fi.vless_display_name', 'FI') }} · FI</div>
                            <textarea readonly rows="4" class="lp-textarea">{{ $row['fiVless'] }}</textarea>
                        </div>
                        <div>
                            <div class="lp-field-label">{{ config('xui.nodes.nl.vless_display_name', 'NL') }} · NL</div>
                            <textarea readonly rows="4" class="lp-textarea">{{ $row['nlVless'] }}</textarea>
                        </div>
                    </div>
                </article>
            @endforeach
        @endif
    </div>
</x-cabinet-layout>

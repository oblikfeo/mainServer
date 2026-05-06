@foreach ($renewalSubscriptions as $sub)
    @php
        $renewPlan = (int) $sub->devices <= (int) $soloDeviceCap ? 'solo' : 'family';
        $renewPack = config('payments.renewals.'.$renewPlan);
        $rows = is_array($renewPack) ? ($renewPack['rows'] ?? []) : [];
        $tariffUi = collect(config('marketing.tariffs', []))->firstWhere('id', $renewPlan);
        $tariffTitle = is_array($tariffUi) ? (string) ($tariffUi['title'] ?? $renewPlan) : $renewPlan;
        $exp = $sub->expiresAt();
    @endphp
    <article
        class="lp-tariff-card lp-tariff-card--renew lp-tariff-card--{{ $renewPlan }}"
        aria-labelledby="renew-sub-{{ $sub->id }}-title"
    >
        <header class="lp-tariff-card__head">
            <h3 class="lp-tariff-card__title" id="renew-sub-{{ $sub->id }}-title">
                №{{ $sub->public_code }} · {{ $tariffTitle }}
            </h3>
            <p class="lp-tariff-card__meta">
                @if ($sub->isExpired())
                    <span class="lp-renew-status-expired">Срок истёк</span>
                @else
                    Активна
                @endif
                · {{ (int) $sub->devices }} устр.
                @if ((int) $sub->quota_gb <= 0)
                    · квота безлимит
                @else
                    · {{ (int) $sub->quota_gb }} ГБ
                @endif
                @if ($exp)
                    · до {{ $exp->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                @endif
            </p>
        </header>
        <div class="lp-tariff-card__body">
            @foreach ($rows as $period => $rRow)
                @php
                    $amt = (string) (($rRow['amount_rub'] ?? 0) > 0 ? $rRow['amount_rub'] : '');
                    $displayRow = ['amount' => $amt];
                @endphp
                <div class="lp-tariff-card__row lp-tariff-card__row--with-pay">
                    <div class="lp-tariff-card__row-left">
                        <span class="lp-tariff-card__period">{{ $period }}</span>
                        <div class="lp-tariff-card__price-block">
                            @include('partials.pricing-tariff-price-block', ['row' => $displayRow])
                        </div>
                        @if (($rRow['days'] ?? 0) > 0 && ($rRow['quota_gb'] ?? 0) > 0)
                            <p class="lp-renew-pack-hint">
                                +{{ (int) $rRow['days'] }} дн. · +{{ (int) $rRow['quota_gb'] }} ГБ
                            </p>
                        @endif
                    </div>
                    <button
                        type="button"
                        class="lp-cab-pay-btn lp-cab-renew-pay-btn"
                        data-purpose="renew"
                        data-subscription-id="{{ $sub->id }}"
                        data-tariff-plan="{{ $renewPlan }}"
                        data-tariff-period="{{ $period }}"
                        data-tariff-amount="{{ $amt }}"
                    >
                        Оплатить продление
                    </button>
                </div>
            @endforeach
        </div>
    </article>
@endforeach

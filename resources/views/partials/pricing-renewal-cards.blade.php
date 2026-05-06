@foreach ($renewalSubscriptions as $sub)
    @php
        $renewPlan = (int) $sub->devices <= (int) $soloDeviceCap ? 'solo' : 'family';
        $renewPack = config('payments.renewals.'.$renewPlan);
        $rows = is_array($renewPack) ? ($renewPack['rows'] ?? []) : [];
        $tariffUi = collect(config('marketing.tariffs', []))->firstWhere('id', $renewPlan);
        $tariffTitle = is_array($tariffUi) ? (string) ($tariffUi['title'] ?? $renewPlan) : $renewPlan;
        $exp = $sub->expiresAt();
    @endphp
    <article class="lp-renew-card" aria-labelledby="renew-sub-{{ $sub->id }}-title">
        <header class="lp-renew-card__head">
            <h3 class="lp-renew-card__title" id="renew-sub-{{ $sub->id }}-title">
                Подписка <span class="lp-renew-card__code">№{{ $sub->public_code }}</span>
            </h3>
            <p class="lp-renew-card__plan-line">Линейка продления: <strong>{{ $tariffTitle }}</strong></p>

            <dl class="lp-renew-stats">
                <div class="lp-renew-stats__row">
                    <dt>Статус</dt>
                    <dd>
                        @if ($sub->isExpired())
                            <span class="lp-renew-badge-expired">истекла</span>
                        @else
                            активна
                        @endif
                    </dd>
                </div>
                <div class="lp-renew-stats__row">
                    <dt>Устройств</dt>
                    <dd>{{ (int) $sub->devices }}</dd>
                </div>
                <div class="lp-renew-stats__row">
                    <dt>Трафик</dt>
                    <dd>
                        @if ((int) $sub->quota_gb <= 0)
                            без лимита
                        @else
                            {{ number_format((int) $sub->quota_gb, 0, ',', ' ') }} ГБ
                        @endif
                    </dd>
                </div>
                @if ($exp)
                    <div class="lp-renew-stats__row">
                        <dt>Действует до</dt>
                        <dd class="lp-renew-stats__date">{{ $exp->timezone(config('app.timezone'))->format('d.m.Y · H:i') }}</dd>
                    </div>
                @else
                    <div class="lp-renew-stats__row">
                        <dt>Срок</dt>
                        <dd>без ограничения по дате</dd>
                    </div>
                @endif
            </dl>
        </header>

        <div class="lp-renew-options" role="list">
            @foreach ($rows as $period => $rRow)
                @php
                    $amt = (int) ($rRow['amount_rub'] ?? 0);
                    $bonusDays = (int) ($rRow['days'] ?? 0);
                    $bonusGb = (int) ($rRow['quota_gb'] ?? 0);
                @endphp
                <div class="lp-renew-option" role="listitem">
                    <div class="lp-renew-option__main">
                        <span class="lp-renew-option__period">{{ $period }}</span>
                        @if ($bonusDays > 0 || $bonusGb > 0)
                            <span class="lp-renew-option__bonus">
                                @if ($bonusDays > 0)
                                    +{{ $bonusDays }} дн.
                                @endif
                                @if ($bonusDays > 0 && $bonusGb > 0)
                                    <span aria-hidden="true"> · </span>
                                @endif
                                @if ($bonusGb > 0)
                                    +{{ number_format($bonusGb, 0, ',', ' ') }} ГБ
                                @endif
                            </span>
                        @endif
                    </div>
                    <span class="lp-renew-option__price" aria-label="Сумма">{{ number_format($amt, 0, ',', ' ') }} ₽</span>
                    <button
                        type="button"
                        class="lp-cab-pay-btn lp-cab-renew-pay-btn"
                        data-purpose="renew"
                        data-subscription-id="{{ $sub->id }}"
                        data-tariff-plan="{{ $renewPlan }}"
                        data-tariff-period="{{ $period }}"
                        data-tariff-amount="{{ $amt }}"
                    >
                        Оплатить
                    </button>
                </div>
            @endforeach
        </div>
    </article>
@endforeach

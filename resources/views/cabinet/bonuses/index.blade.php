<x-cabinet-layout>
    <div class="max-w-4xl mx-auto lp-bonus-page w-full min-w-0">
        <h1 class="lp-page-title">Бонусы</h1>

        @if (! $bonusConfigured)
            <div class="lp-empty lp-empty--compact">
                <p>Дополнительные устройства временно недоступны. Попробуйте позже или напишите в поддержку.</p>
            </div>
        @else
            <section class="lp-bonus-pricing" aria-labelledby="bonus-pricing-title">
                <div class="lp-bonus-pricing__card">
                    <header class="lp-bonus-pricing__head">
                        <h2 class="lp-bonus-pricing__title" id="bonus-pricing-title">+{{ $bonusAddDevices }} устройство к подписке</h2>
                        <p class="lp-bonus-pricing__subtitle">
                            Бонус действует только на выбранную подписку и сгорает вместе с ней.
                            На новую подписку нужно оформить заново.
                        </p>
                    </header>

                    <div class="lp-bonus-pricing__body">
                        <p class="lp-bonus-pricing__formula">
                            Стоимость зависит от того, сколько дней осталось до конца подписки:
                            <strong>+{{ number_format($stepRub, 0, ',', ' ') }} ₽ за каждые {{ $dayBucket }} дн.</strong>
                        </p>

                        <ul class="lp-bonus-pricing__tiers">
                            @foreach ($pricingTiers as $tier)
                                <li @class([
                                    'lp-bonus-pricing__tier',
                                    'lp-bonus-pricing__tier--active' => ! empty($tier['highlight']),
                                    'lp-bonus-pricing__tier--note' => ! empty($tier['is_note']),
                                ])>
                                    <span class="lp-bonus-pricing__tier-range">{{ $tier['range'] }}</span>
                                    @if (empty($tier['is_note']))
                                        <span class="lp-bonus-pricing__tier-price">{{ number_format($tier['amount_rub'], 0, ',', ' ') }} ₽</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>

            @if ($bonusItems->isEmpty())
                <div class="lp-empty lp-empty--compact">
                    <p>Нет активной платной подписки — сначала оформите или продлите доступ.</p>
                    <p class="lp-text-muted-tight">
                        Бонусное устройство можно докупить только к действующей подписке.
                    </p>
                    <a href="{{ route('cabinet.payment') }}" class="lp-btn">К тарифам</a>
                </div>
            @else
                <h2 class="lp-page-section-title">Ваши подписки</h2>
                <div class="lp-renew-stack">
                    @foreach ($bonusItems as $item)
                        @php
                            /** @var \App\Models\Subscription $sub */
                            $sub = $item['subscription'];
                            $exp = $sub->expiresAt();
                            $amountRub = (int) $item['amount_rub'];
                            $remainingDays = $item['remaining_days'];
                        @endphp
                        <article class="lp-renew-card" aria-labelledby="bonus-sub-{{ $sub->id }}-title">
                            <header class="lp-renew-card__head">
                                <h3 class="lp-renew-card__title" id="bonus-sub-{{ $sub->id }}-title">
                                    Подписка <span class="lp-renew-card__code">№{{ $sub->public_code }}</span>
                                </h3>

                                <dl class="lp-renew-stats">
                                    <div class="lp-renew-stats__row">
                                        <dt>Устройств сейчас</dt>
                                        <dd>{{ (int) $sub->devices }}</dd>
                                    </div>
                                    <div class="lp-renew-stats__row">
                                        <dt>Осталось</dt>
                                        <dd>
                                            @if ($remainingDays === null)
                                                без ограничения срока
                                            @else
                                                {{ number_format($remainingDays, 0, ',', ' ') }} дн.
                                            @endif
                                        </dd>
                                    </div>
                                    <div class="lp-renew-stats__row">
                                        <dt>Тариф бонуса</dt>
                                        <dd>{{ $item['tier_range'] }}</dd>
                                    </div>
                                    @if ($exp)
                                        <div class="lp-renew-stats__row">
                                            <dt>Действует до</dt>
                                            <dd class="lp-renew-stats__date">{{ $exp->timezone(config('app.timezone'))->format('d.m.Y · H:i') }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </header>

                            <div class="lp-renew-options" role="list">
                                <div class="lp-renew-option" role="listitem">
                                    <div class="lp-renew-option__lead">
                                        <span class="lp-renew-option__period">+{{ $bonusAddDevices }} устройство</span>
                                    </div>
                                    <div class="lp-renew-option__middle">
                                        <div class="lp-renew-option__bonuses">
                                            <span class="lp-renew-option__bonus-line">до конца подписки №{{ $sub->public_code }}</span>
                                            <span class="lp-renew-option__bonus-line">сейчас {{ $item['tier_range'] }}</span>
                                        </div>
                                    </div>
                                    <div class="lp-renew-option__tail">
                                        <span class="lp-renew-option__price" aria-label="Сумма">{{ number_format($amountRub, 0, ',', ' ') }} ₽</span>
                                        <button
                                            type="button"
                                            class="lp-cab-pay-btn lp-cab-bonus-pay-btn"
                                            data-purpose="extra_device"
                                            data-subscription-id="{{ $sub->id }}"
                                            data-tariff-amount="{{ $amountRub }}"
                                        >
                                            Оплатить
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    @include('partials.cabinet-wata-payment-script')

    <style>
        .lp-f1 .lp-bonus-pricing {
            margin-bottom: 1.5rem;
        }
        .lp-f1 .lp-bonus-pricing__card {
            border: 3px solid var(--lp-ink, #0f172a);
            background: #fff;
            box-shadow: 6px 6px 0 var(--lp-ink, #0f172a);
        }
        .lp-f1 .lp-bonus-pricing__head {
            padding: 1rem 1.1rem 0.95rem;
            border-bottom: 2px solid rgba(15, 23, 42, 0.1);
            background: linear-gradient(180deg, #fff7f2 0%, #fff 100%);
        }
        .lp-f1 .lp-bonus-pricing__title {
            margin: 0 0 0.45rem;
            font-size: 0.9375rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: var(--lp-ink, #0f172a);
        }
        .lp-f1 .lp-bonus-pricing__subtitle {
            margin: 0;
            max-width: 38rem;
            font-size: 0.8125rem;
            line-height: 1.55;
            font-weight: 600;
            color: #475569;
        }
        .lp-f1 .lp-bonus-pricing__body {
            padding: 1rem 1.1rem 1.15rem;
        }
        .lp-f1 .lp-bonus-pricing__formula {
            margin: 0 0 0.85rem;
            font-size: 0.8125rem;
            line-height: 1.55;
            font-weight: 600;
            color: #334155;
        }
        .lp-f1 .lp-bonus-pricing__formula strong {
            color: var(--lp-ink, #0f172a);
            font-weight: 900;
        }
        .lp-f1 .lp-bonus-pricing__tiers {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 0.45rem;
        }
        .lp-f1 .lp-bonus-pricing__tier {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.75rem;
            align-items: center;
            padding: 0.55rem 0.7rem;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #334155;
        }
        .lp-f1 .lp-bonus-pricing__tier--active {
            border-color: var(--lp-orange, #e03e00);
            background: #fff7f2;
            box-shadow: 3px 3px 0 rgba(224, 62, 0, 0.15);
        }
        .lp-f1 .lp-bonus-pricing__tier--note {
            grid-template-columns: 1fr;
            border-style: dashed;
            background: #fff;
            font-weight: 600;
            color: #64748b;
        }
        .lp-f1 .lp-bonus-pricing__tier-price {
            font-size: 0.9375rem;
            font-weight: 900;
            font-variant-numeric: tabular-nums;
            color: var(--lp-ink, #0f172a);
            white-space: nowrap;
        }
        .lp-f1 .lp-bonus-page .lp-page-section-title {
            margin-bottom: 0.85rem;
        }
        @media (max-width: 560px) {
            .lp-f1 .lp-bonus-pricing__head,
            .lp-f1 .lp-bonus-pricing__body {
                padding-inline: 0.85rem;
            }
            .lp-f1 .lp-bonus-pricing__tier {
                grid-template-columns: 1fr;
                gap: 0.25rem;
            }
            .lp-f1 .lp-bonus-pricing__card {
                box-shadow: 4px 4px 0 var(--lp-ink, #0f172a);
            }
        }
    </style>
</x-cabinet-layout>

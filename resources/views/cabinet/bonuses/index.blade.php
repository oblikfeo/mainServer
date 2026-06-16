<x-cabinet-layout>
    <div class="max-w-4xl mx-auto lp-bonus-page w-full min-w-0">
        <h1 class="lp-page-title">Бонусы</h1>

        <p class="lp-bonus-page__lead">
            Дополнительное устройство подключается к текущей подписке и действует только пока она активна.
            При оформлении новой подписки бонус нужно докупить заново.
        </p>

        @if (! $bonusConfigured)
            <div class="lp-empty lp-empty--compact">
                <p>Дополнительные устройства временно недоступны. Попробуйте позже или напишите в поддержку.</p>
            </div>
        @elseif ($activeSubscriptions->isEmpty())
            <div class="lp-empty lp-empty--compact">
                <p>Нет активной платной подписки — сначала оформите или продлите доступ.</p>
                <p class="lp-text-muted-tight">
                    Бонусное устройство можно докупить только к действующей подписке.
                </p>
                <a href="{{ route('cabinet.payment') }}" class="lp-btn">К тарифам</a>
            </div>
        @else
            <div class="lp-renew-stack">
                @foreach ($activeSubscriptions as $sub)
                    @php
                        $exp = $sub->expiresAt();
                    @endphp
                    <article class="lp-renew-card" aria-labelledby="bonus-sub-{{ $sub->id }}-title">
                        <header class="lp-renew-card__head">
                            <h2 class="lp-renew-card__title" id="bonus-sub-{{ $sub->id }}-title">
                                Подписка <span class="lp-renew-card__code">№{{ $sub->public_code }}</span>
                            </h2>

                            <dl class="lp-renew-stats">
                                <div class="lp-renew-stats__row">
                                    <dt>Устройств сейчас</dt>
                                    <dd>{{ (int) $sub->devices }}</dd>
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
                            <div class="lp-renew-option" role="listitem">
                                <div class="lp-renew-option__lead">
                                    <span class="lp-renew-option__period">+{{ $bonusAddDevices }} устройство</span>
                                </div>
                                <div class="lp-renew-option__middle">
                                    <div class="lp-renew-option__bonuses">
                                        <span class="lp-renew-option__bonus-line">к подписке №{{ $sub->public_code }}</span>
                                        <span class="lp-renew-option__bonus-line">до конца её срока</span>
                                    </div>
                                </div>
                                <div class="lp-renew-option__tail">
                                    <span class="lp-renew-option__price" aria-label="Сумма">{{ number_format($bonusAmountRub, 0, ',', ' ') }} ₽</span>
                                    <button
                                        type="button"
                                        class="lp-cab-pay-btn lp-cab-bonus-pay-btn"
                                        data-purpose="extra_device"
                                        data-subscription-id="{{ $sub->id }}"
                                        data-tariff-amount="{{ $bonusAmountRub }}"
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
    </div>

    @include('partials.cabinet-wata-payment-script')

    <style>
        .lp-f1 .lp-bonus-page__lead {
            margin: 0 0 1.25rem;
            max-width: 36rem;
            font-size: 0.875rem;
            line-height: 1.55;
            font-weight: 600;
            color: #475569;
        }
        @media (max-width: 560px) {
            .lp-f1 .lp-bonus-page .lp-page-title {
                margin-bottom: 0.65rem;
            }
            .lp-f1 .lp-bonus-page__lead {
                margin-bottom: 1rem;
                font-size: 0.8125rem;
            }
        }
    </style>
</x-cabinet-layout>

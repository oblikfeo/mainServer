<x-cabinet-layout>
    @include('partials.account-styles')

    @php
        /** @var array<string, mixed> $account */
        $state = $account['state'];
        $sub = $account['subscription'];
        $live = in_array($state, ['active', 'trial'], true);
        $canAdd = $sub && ! $account['unlimited_devices'] && $account['max_extra'] > $account['extra_total'];

        $icon = static function (string $type): string {
            $t = mb_strtolower($type);
            if (str_contains($t, 'windows') || str_contains($t, 'mac') || str_contains($t, 'linux')) {
                return '<svg class="acc-slot__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><rect x="2.5" y="4" width="19" height="12.5"/><path d="M8 20.5h8M12 16.5v4"/></svg>';
            }
            if (str_contains($t, 'ipad')) {
                return '<svg class="acc-slot__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><rect x="4" y="2.5" width="16" height="19"/><path d="M11 18.2h2"/></svg>';
            }

            return '<svg class="acc-slot__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><rect x="6.5" y="2.5" width="11" height="19"/><path d="M11 18.2h2"/></svg>';
        };
    @endphp

    <div class="max-w-4xl mx-auto acc">
        @if (session('status') === 'device-unbound')
            <div class="acc-note" style="margin-bottom:1rem;">Устройство отвязано. Слот свободен — его займёт следующее подключение.</div>
        @endif
        @if (session('status') === 'devices-cleared')
            <div class="acc-note" style="margin-bottom:1rem;">Все устройства отвязаны.</div>
        @endif

        @if (! $sub)
            <div class="acc-block">
                <div class="acc-status"><span class="acc-pill acc-pill--off">Подписка не оформлена</span></div>
                <p class="acc-hint" style="margin-top:0.75rem;">Устройства появятся здесь после оформления подписки.</p>
                <div class="acc-actions"><a href="{{ route('cabinet.payment') }}" class="acc-btn acc-btn--primary">Оплатить</a></div>
            </div>
        @else
            <div class="acc-block">
                <div class="acc-chips">
                    <span class="acc-chip">Устройства</span>
                    @if ($account['unlimited_devices'])
                        <span class="acc-chip acc-chip--ghost">Без лимита</span>
                    @else
                        <span class="acc-chip acc-chip--ghost">Занято {{ min($account['used'], count($account['slots'])) }} из {{ count($account['slots']) }}</span>
                    @endif
                    @unless ($live)
                        <span class="acc-chip acc-chip--orange">Подписка не активна</span>
                    @endunless
                </div>

                <div class="acc-slots">
                    @foreach ($account['slots'] as $slot)
                        @php
                            $device = $slot['device'];
                            $frozen = ! $slot['active'];
                        @endphp
                        <div class="acc-slot {{ $device ? '' : 'acc-slot--empty' }} {{ $frozen ? 'acc-slot--frozen' : '' }}">
                            <div class="acc-slot__kind">
                                @if ($slot['kind'] === 'base')
                                    Входит в подписку
                                @else
                                    Доп. устройство
                                @endif
                            </div>

                            @if ($device)
                                {!! $icon($device['type'] !== '' ? $device['type'] : $device['label']) !!}
                                <div class="acc-slot__name">{{ $device['label'] }}</div>
                                <div class="acc-slot__meta">
                                    @if ($device['seen_at'])
                                        был {{ $device['seen_at']->format('d.m.Y H:i') }}
                                    @else
                                        ещё не подключалось
                                    @endif
                                </div>
                            @else
                                <div class="acc-slot__name" style="margin-top:1.4rem;">Свободно</div>
                                <div class="acc-slot__meta">Добавьте ссылку подписки на новом устройстве</div>
                            @endif

                            @if ($slot['kind'] === 'extra')
                                <div class="acc-slot__paid">
                                    @if ($frozen)
                                        Заморожено · осталось {{ $slot['left_days'] }} дн.
                                    @elseif (! empty($slot['outlives_subscription']))
                                        Оплачено ещё на {{ $slot['left_days'] }} дн.
                                    @elseif ($slot['until'])
                                        Оплачено до {{ $slot['until']->format('d.m.Y') }}
                                    @endif
                                </div>
                            @endif

                            @if ($device)
                                <div class="acc-slot__foot">
                                    <form method="POST" action="{{ route('cabinet.settings.device.detach', $sub) }}" onsubmit="return confirm('Отвязать это устройство?');">
                                        @csrf
                                        <input type="hidden" name="hash" value="{{ $device['hash'] }}">
                                        <button type="submit" class="acc-slot__btn">Отвязать</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @if ($canAdd)
                        <a class="acc-slot acc-slot--add" href="{{ route('cabinet.payment', ['add' => 'device']) }}">
                            <span class="acc-slot__plus">+</span>
                            <span class="acc-slot__addlabel">Добавить устройство</span>
                            <span class="acc-slot__meta">{{ $deviceMonthRub }} ₽ в месяц</span>
                        </a>
                    @elseif (! $account['unlimited_devices'])
                        <div class="acc-slot acc-slot--max">
                            <span class="acc-slot__addlabel">Максимум</span>
                            <span class="acc-slot__meta">{{ count($account['slots']) }} устройств на аккаунте</span>
                        </div>
                    @endif
                </div>

                @if ($account['used'] > 1)
                    <div class="acc-actions">
                        <form method="POST" action="{{ route('cabinet.settings.devices.clear', $sub) }}" onsubmit="return confirm('Отвязать все устройства?');">
                            @csrf
                            <button type="submit" class="acc-btn">Отвязать все</button>
                        </form>
                    </div>
                @endif
            </div>

            @if (! empty($account['waiting']))
                <div class="acc-block">
                    <div class="acc-chips"><span class="acc-chip acc-chip--orange">Ждут оплаты</span></div>
                    <p class="acc-hint" style="margin-bottom:0.9rem;">Оплаченный срок доп. устройства закончился. Эти устройства не подключаются, пока слот не оплачен снова.</p>
                    <div class="acc-slots">
                        @foreach ($account['waiting'] as $device)
                            <div class="acc-slot acc-slot--frozen">
                                <div class="acc-slot__kind">Нет слота</div>
                                {!! $icon($device['type'] !== '' ? $device['type'] : $device['label']) !!}
                                <div class="acc-slot__name">{{ $device['label'] }}</div>
                                <div class="acc-slot__meta">
                                    @if ($device['seen_at']) был {{ $device['seen_at']->format('d.m.Y H:i') }} @endif
                                </div>
                                <div class="acc-slot__foot">
                                    <form method="POST" action="{{ route('cabinet.settings.device.detach', $sub) }}" onsubmit="return confirm('Отвязать это устройство?');">
                                        @csrf
                                        <input type="hidden" name="hash" value="{{ $device['hash'] }}">
                                        <button type="submit" class="acc-slot__btn">Отвязать</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="acc-actions">
                        <a href="{{ route('cabinet.payment', ['add' => 'device']) }}" class="acc-btn acc-btn--primary">Оплатить устройства</a>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-cabinet-layout>

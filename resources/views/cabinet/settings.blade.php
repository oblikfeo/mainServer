<x-cabinet-layout>
    <div class="max-w-4xl mx-auto">
        @if ($subscriptions->isEmpty())
            <div class="lp-empty">
                <p>У вас пока нет привязанных подписок.</p>
                <p>Оформите тариф или дождитесь, пока администратор привяжет подписку к аккаунту. Войдите с тем же email, что указали при оформлении.</p>
                <a href="{{ url('/#tarify') }}" class="lp-btn">Тарифы на главной</a>
            </div>
        @else
            <div class="lp-settings-devices">
                <article class="lp-card" style="margin-bottom: 1rem;">
                    <div class="lp-card__head">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-black uppercase tracking-wider text-slate-600">Привязанные устройства</span>
                        </div>
                        <p class="lp-card__head-note">Happ передаёт отпечаток устройства; мы храним только SHA-256. Название телефона или ПК недоступно — показываем короткий код.</p>
                    </div>
                    <div class="lp-card__body lp-stack text-sm text-slate-700">
                        @if (session('status') === 'device-unbound')
                            <div class="lp-warn-box" style="background:#dcfce7;">
                                Устройство отвязано. Следующее подключение Happ займёт свободный слот.
                            </div>
                        @endif
                        @if (session('status') === 'devices-cleared')
                            <div class="lp-warn-box" style="background:#dcfce7;">
                                Все привязки по подписке сброшены.
                            </div>
                        @endif

                        @if (! filter_var((string) config('xui.feed_require_hwid', true), FILTER_VALIDATE_BOOL))
                            <p class="m-0 font-semibold text-slate-800">Проверка устройств на стороне сервера отключена — список привязок может быть пуст.</p>
                        @endif

                        @foreach ($subscriptions as $sub)
                            @php
                                $hashes = $sub->bound_hwid_hashes;
                                $list = is_array($hashes)
                                    ? array_values(array_filter($hashes, static fn ($h) => is_string($h) && strlen($h) === 64))
                                    : [];
                                $exp = $sub->expiresAt();
                            @endphp
                            <div class="lp-device-sub-block">
                                <div class="flex flex-wrap items-baseline justify-between gap-2 mb-2">
                                    <span class="font-black text-black tabular-nums">#{{ $sub->public_code }}</span>
                                    @if ($sub->isExpired())
                                        <span class="lp-badge-pill lp-badge-pill--bad">Истекла</span>
                                    @else
                                        <span class="lp-badge-pill lp-badge-pill--ok">Активна</span>
                                    @endif
                                </div>
                                <p class="text-xs font-bold uppercase text-slate-500 m-0 mb-2">
                                    Слотов: {{ max(0, (int) $sub->devices) }} · привязано: {{ count($list) }}
                                    @if ($exp)
                                        · до {{ $exp->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                                    @endif
                                </p>

                                @if (max(0, (int) $sub->devices) < 1)
                                    <p class="m-0 text-slate-600">Для этой подписки не задан лимит устройств — привязка Happ не используется.</p>
                                @elseif ($list === [])
                                    <p class="m-0 text-slate-600">Пока ни одно устройство не подключалось по этой подписке (обновите конфиг в Happ).</p>
                                @else
                                    <ul class="lp-device-list m-0 p-0 list-none space-y-2">
                                        @foreach ($list as $hash)
                                            <li class="lp-device-row flex flex-wrap items-center justify-between gap-2 border-2 border-black bg-slate-50 px-2 py-2">
                                                <span class="lp-mono text-xs font-bold text-slate-800 break-all">
                                                    {{ substr($hash, 0, 12) }}…{{ substr($hash, -8) }}
                                                </span>
                                                <form
                                                    method="POST"
                                                    action="{{ route('cabinet.settings.device.detach', $sub) }}"
                                                    class="shrink-0"
                                                    onsubmit="return confirm('Отвязать это устройство?');"
                                                >
                                                    @csrf
                                                    <input type="hidden" name="hash" value="{{ $hash }}" />
                                                    <button type="submit" class="lp-secondary-outline text-xs py-1 px-2">Отвязать</button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <form
                                        method="POST"
                                        action="{{ route('cabinet.settings.devices.clear', $sub) }}"
                                        class="mt-3"
                                        onsubmit="return confirm('Сбросить все привязки по этой подписке?');"
                                    >
                                        @csrf
                                        <button type="submit" class="lp-secondary-outline text-xs py-1 px-2">Сбросить все привязки</button>
                                    </form>
                                @endif
                            </div>
                            @if (! $loop->last)
                                <hr class="border-0 border-t-2 border-black my-4" />
                            @endif
                        @endforeach
                    </div>
                </article>
            </div>
        @endif
    </div>
</x-cabinet-layout>

@php
    $e = $quests->emailQuest;
    $f1 = $quests->firstRegQuest;
    $adev = $quests->activeDevicesQuest;
    $a5 = $quests->active5Quest;
    $a10 = $quests->active10Quest;
    $mDev = (int) ($adev['target'] ?? config('referral.active_paid_milestone_devices', 7));
@endphp
<div class="lp-profile-block lp-profile-accordion lp-ref-section" x-data="{ open: false }">
    <button
        type="button"
        class="lp-profile-accordion__trigger"
        @click="open = !open"
        :aria-expanded="open"
        aria-controls="ref-quests-panel"
        id="ref-quests-title"
    >
        <span class="lp-profile-accordion__title">Задания</span>
        <span class="lp-profile-accordion__chev" :class="{ 'lp-profile-accordion__chev--open': open }" aria-hidden="true">▾</span>
    </button>
    <div class="lp-profile-accordion__panel" id="ref-quests-panel" x-show="open" x-cloak x-transition role="region" aria-labelledby="ref-quests-title">
    <p class="lp-ref-quests-lead">Выполните условие — награда указана в блоках ниже.</p>

    <div class="lp-ref-quests">
        <article class="lp-ref-quest @if($e['done']) lp-ref-quest--done @endif">
            <span class="lp-ref-quest__badge" aria-hidden="true">@if($e['done'])✓@else 1 @endif</span>
            <div class="lp-ref-quest__body">
                <div class="lp-ref-quest__top">
                    <h3 class="lp-ref-quest__name">Подтверждение почты</h3>
                    <span class="lp-ref-quest__ratio tabular-nums">{{ $e['ratio'] }}</span>
                </div>
                <div class="lp-ref-bar" role="img" aria-label="{{ $e['current'] }} из {{ $e['target'] }}">
                    <span class="lp-ref-bar__fill" style="width:{{ min(100, $e['bar']) }}%;"></span>
                </div>
                <p class="lp-ref-quest__status">{{ $e['status'] }}</p>
            </div>
        </article>

        <article class="lp-ref-quest @if($f1['done']) lp-ref-quest--done @endif">
            <span class="lp-ref-quest__badge" aria-hidden="true">@if($f1['done'])✓@else 2 @endif</span>
            <div class="lp-ref-quest__body">
                <div class="lp-ref-quest__top">
                    <h3 class="lp-ref-quest__name">Первая регистрация</h3>
                    <span class="lp-ref-quest__ratio tabular-nums">{{ $f1['ratio'] }}</span>
                </div>
                <div class="lp-ref-quest__prize-wrap">
                    <div class="lp-ref-quest__prize-split">
                        <div class="lp-ref-quest__prize-cell">
                            <span class="lp-ref-quest__prize-who">Вам</span>
                            <span class="lp-ref-quest__prize-val">+1 день</span>
                        </div>
                        <div class="lp-ref-quest__prize-cell">
                            <span class="lp-ref-quest__prize-who">Другу</span>
                            <span class="lp-ref-quest__prize-val">2 тест-ключа</span>
                        </div>
                    </div>
                </div>
                <div class="lp-ref-bar" role="img" aria-label="{{ $f1['current'] }} из {{ $f1['target'] }}">
                    <span class="lp-ref-bar__fill" style="width:{{ min(100, $f1['bar']) }}%;"></span>
                </div>
                <p class="lp-ref-quest__status">{{ $f1['status'] }}</p>
            </div>
        </article>

        <article class="lp-ref-quest @if($a5['done']) lp-ref-quest--done @endif">
            <span class="lp-ref-quest__badge" aria-hidden="true">@if($a5['done'])✓@else 3 @endif</span>
            <div class="lp-ref-quest__body">
                <div class="lp-ref-quest__top">
                    <h3 class="lp-ref-quest__name">5 активных оплат</h3>
                    <span class="lp-ref-quest__ratio tabular-nums">{{ $a5['ratio'] }}</span>
                </div>
                <div class="lp-ref-quest__prize-wrap">
                    <div class="lp-ref-quest__prize-feature">
                        <div class="lp-ref-quest__prize-feature-main">
                            <span class="lp-ref-quest__prize-feature-title">1 месяц бесплатно</span>
                            <span class="lp-ref-quest__prize-feature-sub">к вашей подписке</span>
                        </div>
                        <span class="lp-ref-quest__tag">эксклюзив</span>
                    </div>
                </div>
                <div class="lp-ref-bar" role="img" aria-label="{{ $a5['current'] }} из {{ $a5['target'] }}">
                    <span class="lp-ref-bar__fill" style="width:{{ min(100, $a5['bar']) }}%;"></span>
                </div>
                <p class="lp-ref-quest__status">{{ $a5['status'] }}</p>
            </div>
        </article>

        <article class="lp-ref-quest @if($adev['done']) lp-ref-quest--done @endif">
            <span class="lp-ref-quest__badge" aria-hidden="true">@if($adev['done'])✓@else 4 @endif</span>
            <div class="lp-ref-quest__body">
                <div class="lp-ref-quest__top">
                    <h3 class="lp-ref-quest__name">{{ $mDev }} активных оплат</h3>
                    <span class="lp-ref-quest__ratio tabular-nums">{{ $adev['ratio'] }}</span>
                </div>
                <div class="lp-ref-quest__prize-wrap">
                    <div class="lp-ref-quest__prize-feature">
                        <div class="lp-ref-quest__prize-feature-main">
                            <span class="lp-ref-quest__prize-feature-title">+1 устройство</span>
                            <span class="lp-ref-quest__prize-feature-sub">навсегда</span>
                        </div>
                        <span class="lp-ref-quest__tag">эксклюзив</span>
                    </div>
                </div>
                <div class="lp-ref-bar" role="img" aria-label="{{ $adev['current'] }} из {{ $adev['target'] }}">
                    <span class="lp-ref-bar__fill" style="width:{{ min(100, $adev['bar']) }}%;"></span>
                </div>
                <p class="lp-ref-quest__status">{{ $adev['status'] }}</p>
            </div>
        </article>

        <article class="lp-ref-quest @if($a10['done']) lp-ref-quest--done @endif">
            <span class="lp-ref-quest__badge" aria-hidden="true">@if($a10['done'])✓@else 5 @endif</span>
            <div class="lp-ref-quest__body">
                <div class="lp-ref-quest__top">
                    <h3 class="lp-ref-quest__name">10 активных оплат</h3>
                    <span class="lp-ref-quest__ratio tabular-nums">{{ $a10['ratio'] }}</span>
                </div>
                <div class="lp-ref-quest__prize-wrap">
                    <div class="lp-ref-quest__prize-feature">
                        <div class="lp-ref-quest__prize-feature-main">
                            <span class="lp-ref-quest__prize-feature-title">3 месяца бесплатно</span>
                            <span class="lp-ref-quest__prize-feature-sub">к вашей подписке</span>
                        </div>
                        <span class="lp-ref-quest__tag">эксклюзив</span>
                    </div>
                </div>
                <div class="lp-ref-bar" role="img" aria-label="{{ $a10['current'] }} из {{ $a10['target'] }}">
                    <span class="lp-ref-bar__fill" style="width:{{ min(100, $a10['bar']) }}%;"></span>
                </div>
                <p class="lp-ref-quest__status">{{ $a10['status'] }}</p>
            </div>
        </article>
    </div>
    </div>
</div>

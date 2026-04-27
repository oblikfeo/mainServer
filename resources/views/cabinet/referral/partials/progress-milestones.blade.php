@php
    $e = $quests->emailQuest;
    $f1 = $quests->firstRegQuest;
    $fp = $quests->firstPayQuest;
    $a4 = $quests->active4Quest;
    $a10 = $quests->active10Quest;
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
                            <span class="lp-ref-quest__prize-val">2 тест-ключа по 8 ч</span>
                        </div>
                    </div>
                </div>
                <div class="lp-ref-bar" role="img" aria-label="{{ $f1['current'] }} из {{ $f1['target'] }}">
                    <span class="lp-ref-bar__fill" style="width:{{ min(100, $f1['bar']) }}%;"></span>
                </div>
                <p class="lp-ref-quest__status">{{ $f1['status'] }}</p>
            </div>
        </article>

        <article class="lp-ref-quest @if($fp['done']) lp-ref-quest--done @endif">
            <span class="lp-ref-quest__badge" aria-hidden="true">@if($fp['done'])✓@else 3 @endif</span>
            <div class="lp-ref-quest__body">
                <div class="lp-ref-quest__top">
                    <h3 class="lp-ref-quest__name">Первая оплата</h3>
                    <span class="lp-ref-quest__ratio tabular-nums">{{ $fp['ratio'] }}</span>
                </div>
                <div class="lp-ref-quest__prize-wrap">
                    <div class="lp-ref-quest__prize-split">
                        <div class="lp-ref-quest__prize-cell">
                            <span class="lp-ref-quest__prize-who">Вам</span>
                            <span class="lp-ref-quest__prize-val">+7 дней к подписке</span>
                        </div>
                        <div class="lp-ref-quest__prize-cell">
                            <span class="lp-ref-quest__prize-who">Другу</span>
                            <span class="lp-ref-quest__prize-val">+7 дней к подписке</span>
                        </div>
                    </div>
                </div>
                <div class="lp-ref-bar" role="img" aria-label="{{ $fp['current'] }} из {{ $fp['target'] }}">
                    <span class="lp-ref-bar__fill" style="width:{{ min(100, $fp['bar']) }}%;"></span>
                </div>
                <p class="lp-ref-quest__status">{{ $fp['status'] }}</p>
            </div>
        </article>

        <article class="lp-ref-quest @if($a4['done']) lp-ref-quest--done @endif">
            <span class="lp-ref-quest__badge" aria-hidden="true">@if($a4['done'])✓@else 4 @endif</span>
            <div class="lp-ref-quest__body">
                <div class="lp-ref-quest__top">
                    <h3 class="lp-ref-quest__name">4 активные оплаты</h3>
                    <span class="lp-ref-quest__ratio tabular-nums">{{ $a4['ratio'] }}</span>
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
                <div class="lp-ref-bar" role="img" aria-label="{{ $a4['current'] }} из {{ $a4['target'] }}">
                    <span class="lp-ref-bar__fill" style="width:{{ min(100, $a4['bar']) }}%;"></span>
                </div>
                <p class="lp-ref-quest__status">{{ $a4['status'] }}</p>
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
                            <span class="lp-ref-quest__prize-feature-title">Безлимитный трафик</span>
                            <span class="lp-ref-quest__prize-feature-sub">навсегда</span>
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

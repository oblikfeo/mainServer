@extends('views2::layouts.marketing')

@php
    $brand = config('marketing.brand_name', 'Надежда');
    $tg = config('marketing.telegram_support_url', config('marketing.telegram_url', 'https://t.me/nadezhda_tehsup'));
@endphp

@section('title', $brand.' — задания и награды')
@section('meta_description', 'Выполняйте задания — получайте награды: дни, эксклюзивные возможности и безлимитный трафик.')

@push('styles')
    @include('views2::partials.lp-f1-styles')
    @include('views2::partials.lp-header-views2-styles')
    @include('views2::partials.lp-views2-responsive-styles')
    @include('cabinet.nice.partials.nice-styles')
@endpush

@section('content')
<div class="lp-f1 lp-f1-body">
    <div class="lp-container">
        <header class="lp-header lp-header-v2 lp-header--drawer">
            <div class="lp-header__bar">
                <a href="{{ url('/') }}" class="lp-brand-line" style="text-decoration:none;">
                    <span class="lp-logo-heavy">{{ mb_strtoupper($brand, 'UTF-8') }}</span>
                    <span class="lp-logo-vpn">VPN</span>
                </a>
                <nav class="lp-header__nav" id="lp-main-nav" aria-label="Быстрые ссылки">
                    <a href="{{ route('dashboard') }}">Кабинет</a>
                    <a href="{{ route('cabinet.referral') }}">Реферальная</a>
                    <a href="{{ route('cabinet.payment') }}">Тарифы</a>
                </nav>
                <button
                    type="button"
                    class="lp-nav-toggle"
                    id="lp-nav-toggle"
                    aria-expanded="false"
                    aria-controls="lp-main-nav"
                    aria-label="Открыть меню"
                >
                    <span class="lp-nav-toggle__bars" aria-hidden="true"></span>
                </button>
                <a href="{{ route('dashboard') }}" class="lp-header-cta">← В кабинет</a>
            </div>
        </header>

        <section class="lp-nice-hero">
            <span class="lp-nice-hero__kicker">
                <span class="lp-nice-hero__kicker-dot" aria-hidden="true"></span>
                Задания и награды
            </span>
            <h1 class="lp-nice-hero__title">
                Выполняй — <span class="lp-nice-hero__title-em">получай</span>
            </h1>
            <p class="lp-nice-hero__lead">
                Пять простых шагов — и к подписке добавляются дни, новые устройства и безлимитный трафик навсегда. Прогресс считается автоматически.
            </p>

            <div class="lp-nice-hero__stats">
                <div class="lp-nice-hero__counter" role="group" aria-label="Сколько заданий выполнено">
                    <span class="lp-nice-hero__counter-big">
                        {{ $doneCount }}<small>/{{ $totalCount }}</small>
                    </span>
                    <span class="lp-nice-hero__counter-label">
                        заданий выполнено
                        <strong>
                            @if ($doneCount === 0)
                                начните с любого
                            @elseif ($doneCount < $totalCount)
                                осталось {{ $totalCount - $doneCount }}
                            @else
                                полный набор
                            @endif
                        </strong>
                    </span>
                </div>

                <div class="lp-nice-hero__bar-wrap" role="group" aria-label="Общий прогресс">
                    <div class="lp-nice-hero__bar-label">
                        <span>Общий прогресс</span>
                        <strong>{{ $overallPercent }}%</strong>
                    </div>
                    <div class="lp-nice-hero__bar" aria-hidden="true">
                        <span
                            class="lp-nice-hero__bar-fill @if($overallPercent >= 100) lp-nice-hero__bar-fill--full @endif"
                            style="width: {{ min(100, max(0, $overallPercent)) }}%;"
                        ></span>
                    </div>
                </div>
            </div>
        </section>

        <div class="lp-nice-marquee" aria-hidden="true">
            <div class="lp-nice-marquee__track">
                <span class="lp-nice-marquee__segment">
                    &nbsp;&nbsp;★ Выполняй задания ★ <em>получай награды</em> ★ дни подписки ★ <em>устройства навсегда</em> ★ безлимит трафика ★&nbsp;&nbsp;
                </span>
                <span class="lp-nice-marquee__segment">
                    &nbsp;&nbsp;★ Выполняй задания ★ <em>получай награды</em> ★ дни подписки ★ <em>устройства навсегда</em> ★ безлимит трафика ★&nbsp;&nbsp;
                </span>
            </div>
        </div>

        <section class="lp-nice-section" aria-labelledby="lp-nice-tasks-title">
            <header class="lp-nice-section__head">
                <h2 class="lp-nice-section__title" id="lp-nice-tasks-title">Задания</h2>
                <span class="lp-nice-section__hint">нажмите на карточку — раскроется подробный план</span>
            </header>

            <div class="lp-nice-quests" role="list">
                @foreach ($questList as $q)
                    @php
                        $data = $q['data'];
                        $done = (bool) ($data['done'] ?? false);
                        $barPercent = max(0, min(100, (float) ($data['bar'] ?? 0)));
                        $panelId = 'lp-nice-panel-'.$q['key'];
                    @endphp
                    <article
                        class="lp-nice-quest @if($done) lp-nice-quest--done @endif"
                        :class="{ 'lp-nice-quest--open': open }"
                        x-data="{ open: {{ $done ? 'false' : 'false' }} }"
                        role="listitem"
                    >
                        <button
                            type="button"
                            class="lp-nice-quest__trigger"
                            @click="open = !open"
                            :aria-expanded="open"
                            aria-controls="{{ $panelId }}"
                        >
                            <span class="lp-nice-quest__badge" aria-hidden="true">
                                @if ($done) ✓ @else {{ $q['num'] }} @endif
                            </span>
                            <span class="lp-nice-quest__head">
                                <h3 class="lp-nice-quest__title">{{ $q['title'] }}</h3>
                                <p class="lp-nice-quest__sub">{{ $q['subtitle'] }}</p>
                            </span>
                            <span class="lp-nice-quest__ratio" aria-hidden="true">
                                <span class="lp-nice-quest__ratio-val tabular-nums">{{ $data['ratio'] }}</span>
                                <span class="lp-nice-quest__chev" :class="{ 'lp-nice-quest__chev--open': open }">▾</span>
                            </span>
                            <span class="lp-nice-quest__mini-bar" role="img" aria-label="Прогресс: {{ $data['current'] }} из {{ $data['target'] }}">
                                <span class="lp-nice-quest__mini-bar-fill" style="width: {{ $barPercent }}%;"></span>
                            </span>
                        </button>

                        <div
                            class="lp-nice-quest__panel"
                            id="{{ $panelId }}"
                            x-show="open"
                            x-cloak
                            x-transition.duration.150ms
                        >
                            <div class="lp-nice-quest__panel-inner">
                                <div>
                                    <ol class="lp-nice-steps">
                                        @foreach ($q['steps'] as $step)
                                            <li>{{ $step }}</li>
                                        @endforeach
                                    </ol>
                                </div>

                                <aside class="lp-nice-aside">
                                    @if (!empty($q['feature']))
                                        <div class="lp-nice-feature">
                                            @if (!empty($q['feature']['tag']))
                                                <span class="lp-nice-feature__tag">{{ $q['feature']['tag'] }}</span>
                                            @endif
                                            <span class="lp-nice-feature__title">{{ $q['feature']['title'] }}</span>
                                            <span class="lp-nice-feature__sub">{{ $q['feature']['sub'] }}</span>
                                        </div>
                                    @elseif (!empty($q['reward_you']) || !empty($q['reward_friend']))
                                        <div class="lp-nice-rewards-grid">
                                            @if (!empty($q['reward_you']))
                                                <div class="lp-nice-reward-box @if(empty($q['reward_friend'])) lp-nice-reward-box--single @endif">
                                                    <span class="lp-nice-reward-box__kicker">Вам</span>
                                                    <span class="lp-nice-reward-box__val">{{ $q['reward_you'] }}</span>
                                                </div>
                                            @endif
                                            @if (!empty($q['reward_friend']))
                                                <div class="lp-nice-reward-box @if(empty($q['reward_you'])) lp-nice-reward-box--single @endif">
                                                    <span class="lp-nice-reward-box__kicker">Другу</span>
                                                    <span class="lp-nice-reward-box__val">{{ $q['reward_friend'] }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <p class="lp-nice-status">{{ $data['status'] }}</p>

                                    <div class="lp-nice-actions">
                                        @if ($done)
                                            <span class="lp-nice-btn lp-nice-btn--done" aria-disabled="true">
                                                {{ $q['done_cta']['label'] ?? 'Выполнено' }}
                                            </span>
                                        @else
                                            @php $cta = $q['cta'] ?? null; @endphp
                                            @if (!empty($cta))
                                                @if (!empty($cta['copy']))
                                                    <button
                                                        type="button"
                                                        class="lp-nice-btn @if(!empty($cta['primary'])) lp-nice-btn--primary @endif"
                                                        :class="copied ? 'lp-nice-btn--copied' : ''"
                                                        x-data="{ copied: false }"
                                                        @click="
                                                            const txt = @js($cta['copy']);
                                                            const done = () => { copied = true; setTimeout(() => copied = false, 1800); };
                                                            if (navigator.clipboard && window.isSecureContext) {
                                                                navigator.clipboard.writeText(txt).then(done).catch(() => {
                                                                    const ta = document.createElement('textarea');
                                                                    ta.value = txt; document.body.appendChild(ta); ta.select();
                                                                    try { document.execCommand('copy'); done(); } catch (e) {}
                                                                    document.body.removeChild(ta);
                                                                });
                                                            } else {
                                                                const ta = document.createElement('textarea');
                                                                ta.value = txt; document.body.appendChild(ta); ta.select();
                                                                try { document.execCommand('copy'); done(); } catch (e) {}
                                                                document.body.removeChild(ta);
                                                            }
                                                        "
                                                    >
                                                        <span x-show="!copied">{{ $cta['label'] }}</span>
                                                        <span x-show="copied" x-cloak>Скопировано ✓</span>
                                                    </button>
                                                @elseif (!empty($cta['href']))
                                                    <a
                                                        href="{{ $cta['href'] }}"
                                                        class="lp-nice-btn @if(!empty($cta['primary'])) lp-nice-btn--primary @endif"
                                                    >{{ $cta['label'] }}</a>
                                                @endif
                                            @endif

                                            @if (!empty($q['cta_secondary']))
                                                <a
                                                    href="{{ $q['cta_secondary']['href'] }}"
                                                    class="lp-nice-btn lp-nice-btn--ghost"
                                                >{{ $q['cta_secondary']['label'] }}</a>
                                            @endif
                                        @endif
                                    </div>
                                </aside>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="lp-nice-wall" aria-labelledby="lp-nice-rewards-title">
            <header class="lp-nice-section__head">
                <h2 class="lp-nice-section__title" id="lp-nice-rewards-title">Стена наград</h2>
                <span class="lp-nice-section__hint">сюда попадают уже полученные бонусы</span>
            </header>

            @if (count($rewards) > 0)
                <div class="lp-nice-wall__grid">
                    @foreach ($rewards as $r)
                        <div class="lp-nice-wall__card">
                            <span class="lp-nice-wall__icon" aria-hidden="true">{{ $r['icon'] }}</span>
                            <div>
                                <p class="lp-nice-wall__label">{{ $r['label'] }}</p>
                                <span class="lp-nice-wall__sub">{{ $r['sub'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="lp-nice-wall__empty">
                    Пока пусто — выполните первое задание, и награда появится здесь.
                </div>
            @endif
        </section>

        <footer class="lp-nice-footer">
            <a href="{{ route('dashboard') }}">← В кабинет</a>
            <a href="{{ route('cabinet.referral') }}">Реферальная программа</a>
            <a href="{{ $tg }}" target="_blank" rel="noopener noreferrer">Поддержка</a>
        </footer>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var toggle = document.getElementById('lp-nav-toggle');
    var nav = document.getElementById('lp-main-nav');
    if (!toggle || !nav) return;
    function setOpen(open) {
        nav.classList.toggle('lp-header__nav--open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
    }
    toggle.addEventListener('click', function () {
        setOpen(!nav.classList.contains('lp-header__nav--open'));
    });
    nav.querySelectorAll('a').forEach(function (a) {
        a.addEventListener('click', function () { setOpen(false); });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setOpen(false);
    });
})();
</script>
@endpush

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
        <article class="lp-ref-quest lp-ref-quest--done">
            <span class="lp-ref-quest__badge" aria-hidden="true">✓</span>
            <div class="lp-ref-quest__body">
                <div class="lp-ref-quest__top">
                    <h3 class="lp-ref-quest__name">Подтверждение почты</h3>
                    <span class="lp-ref-quest__ratio tabular-nums">1/1</span>
                </div>
                <div class="lp-ref-bar" role="img" aria-label="1 из 1">
                    <span class="lp-ref-bar__fill" style="width:100%;"></span>
                </div>
                <p class="lp-ref-quest__status">Почта подтверждена</p>
            </div>
        </article>

        <article class="lp-ref-quest">
            <span class="lp-ref-quest__badge" aria-hidden="true">2</span>
            <div class="lp-ref-quest__body">
                <div class="lp-ref-quest__top">
                    <h3 class="lp-ref-quest__name">Первая регистрация</h3>
                    <span class="lp-ref-quest__ratio tabular-nums">0/1</span>
                </div>
                <div class="lp-ref-quest__prize-wrap">
                    <div class="lp-ref-quest__prize-split">
                        <div class="lp-ref-quest__prize-cell">
                            <span class="lp-ref-quest__prize-who">Вам</span>
                            <span class="lp-ref-quest__prize-val">+1 день</span>
                        </div>
                        <div class="lp-ref-quest__prize-cell">
                            <span class="lp-ref-quest__prize-who">Другу</span>
                            <span class="lp-ref-quest__prize-val">+8 ч к тесту</span>
                        </div>
                    </div>
                </div>
                <div class="lp-ref-bar" role="img" aria-label="0 из 1">
                    <span class="lp-ref-bar__fill" style="width:0%;"></span>
                </div>
                <p class="lp-ref-quest__status">Ждём первую регистрацию по ссылке</p>
            </div>
        </article>

        <article class="lp-ref-quest">
            <span class="lp-ref-quest__badge" aria-hidden="true">3</span>
            <div class="lp-ref-quest__body">
                <div class="lp-ref-quest__top">
                    <h3 class="lp-ref-quest__name">Первая оплата</h3>
                    <span class="lp-ref-quest__ratio tabular-nums">1/3</span>
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
                <div class="lp-ref-bar" role="img" aria-label="1 из 3">
                    <span class="lp-ref-bar__fill" style="width:33%;"></span>
                </div>
                <p class="lp-ref-quest__status">Ещё 2 первые оплаты до полного набора</p>
            </div>
        </article>

        <article class="lp-ref-quest">
            <span class="lp-ref-quest__badge" aria-hidden="true">4</span>
            <div class="lp-ref-quest__body">
                <div class="lp-ref-quest__top">
                    <h3 class="lp-ref-quest__name">4 активные оплаты</h3>
                    <span class="lp-ref-quest__ratio tabular-nums">2/4</span>
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
                <div class="lp-ref-bar" role="img" aria-label="2 из 4">
                    <span class="lp-ref-bar__fill" style="width:50%;"></span>
                </div>
                <p class="lp-ref-quest__status">Ещё 2 активные оплаты</p>
            </div>
        </article>

        <article class="lp-ref-quest">
            <span class="lp-ref-quest__badge" aria-hidden="true">5</span>
            <div class="lp-ref-quest__body">
                <div class="lp-ref-quest__top">
                    <h3 class="lp-ref-quest__name">10 активных оплат</h3>
                    <span class="lp-ref-quest__ratio tabular-nums">6/10</span>
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
                <div class="lp-ref-bar" role="img" aria-label="6 из 10">
                    <span class="lp-ref-bar__fill" style="width:60%;"></span>
                </div>
                <p class="lp-ref-quest__status">Ещё 4 активные оплаты</p>
            </div>
        </article>
    </div>
    </div>
</div>

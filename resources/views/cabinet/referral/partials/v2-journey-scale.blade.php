<section class="lp-profile-block lp-ref-section lp-ref2-hero" aria-labelledby="ref2-journey-title">
    <h2 id="ref2-journey-title" class="text-xs font-black uppercase tracking-wider text-slate-600 mb-0">Ваш путь и награды</h2>
    <p class="lp-ref2-hero__lead">Оранжевая часть шкалы — уже пройденный путь. У отметок видно, что получите на каждом этапе.</p>

    <div class="lp-ref2-scale" aria-label="Прогресс по реферальной программе">
        <div class="lp-ref2-scale__meta">
            <span class="lp-ref2-scale__meta-label">Пройдено пути</span>
            <span class="lp-ref2-scale__meta-value tabular-nums">{{ (int) $journeyFillPercent }}%</span>
        </div>

        <div class="lp-ref2-scale__viewport">
            <div class="lp-ref2-scale__rail" aria-hidden="true"></div>
            <div class="lp-ref2-scale__fill" style="width: {{ min(100, max(0, (int) $journeyFillPercent)) }}%;" aria-hidden="true"></div>

            <ol class="lp-ref2-scale__stops">
                <li class="lp-ref2-scale__stop" style="--stop-pct: 0%;">
                    <span class="lp-ref2-scale__stop-title">Регистрация</span>
                    <span class="lp-ref2-scale__stop-prize">+1 день · +8 ч теста</span>
                    <span class="lp-ref2-scale__stop-dot" aria-hidden="true"></span>
                </li>
                <li class="lp-ref2-scale__stop" style="--stop-pct: 33.333%;">
                    <span class="lp-ref2-scale__stop-title">Первая оплата</span>
                    <span class="lp-ref2-scale__stop-prize">+7 дн. · +7 дн.</span>
                    <span class="lp-ref2-scale__stop-dot" aria-hidden="true"></span>
                </li>
                <li class="lp-ref2-scale__stop lp-ref2-scale__stop--accent" style="--stop-pct: 66.666%;">
                    <span class="lp-ref2-scale__stop-title">4 оплаты</span>
                    <span class="lp-ref2-scale__stop-prize">+1 устройство</span>
                    <span class="lp-ref2-scale__stop-tag">эксклюзив</span>
                    <span class="lp-ref2-scale__stop-dot" aria-hidden="true"></span>
                </li>
                <li class="lp-ref2-scale__stop lp-ref2-scale__stop--accent" style="--stop-pct: 100%;">
                    <span class="lp-ref2-scale__stop-title">10 оплат</span>
                    <span class="lp-ref2-scale__stop-prize">Безлимит трафика</span>
                    <span class="lp-ref2-scale__stop-tag">эксклюзив</span>
                    <span class="lp-ref2-scale__stop-dot" aria-hidden="true"></span>
                </li>
            </ol>
        </div>
    </div>
</section>

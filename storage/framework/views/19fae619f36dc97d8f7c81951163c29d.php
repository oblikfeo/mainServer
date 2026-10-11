

<?php
    $brand = config('marketing.brand_name', 'Надежда');
    $tg = config('marketing.telegram_support_url', config('marketing.telegram_url', 'https://t.me/nadezhda_tehsup'));
    $isPaid = $order->status === 'paid';
    $subUrl = $subscription?->shareableSubUrl();
    $iosAppUrl = config('marketing.apps.ios_url');
    $androidAppUrl = config('marketing.apps.android_url');
?>

<?php $__env->startSection('title', $brand.' — подписка готова'); ?>
<?php $__env->startSection('meta_description', 'Оплата прошла успешно. Скопируйте ссылку подписки и добавьте её в Happ.'); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('views2::partials.lp-f1-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('views2::partials.lp-header-views2-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('views2::partials.lp-views2-responsive-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('cabinet.nice.partials.nice-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('quick-buy.partials.buy-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="lp-f1 lp-f1-body">
    <div class="lp-container">
        <header class="lp-header lp-header-v2">
            <div class="lp-header__bar">
                <a href="<?php echo e(url('/')); ?>" class="lp-brand-line" style="text-decoration:none;">
                    <span class="lp-logo-heavy"><?php echo e(mb_strtoupper($brand, 'UTF-8')); ?></span>
                    <span class="lp-logo-vpn">VPN</span>
                </a>
                <?php if(auth()->guard()->check()): ?>
                    <a href="<?php echo e(route('dashboard')); ?>" class="lp-header-cta">Кабинет</a>
                <?php else: ?>
                    <a href="<?php echo e(route('login')); ?>" class="lp-header-cta">Войти</a>
                <?php endif; ?>
            </div>
        </header>

        <section class="lp-nice-hero">
            <?php if($isPaid && $subscription !== null): ?>
                <span class="lp-nice-hero__kicker">
                    <span class="lp-nice-hero__kicker-dot" aria-hidden="true"></span>
                    Оплата прошла
                </span>
                <h1 class="lp-nice-hero__title">
                    Подписка <span class="lp-nice-hero__title-em">готова</span>
                </h1>
                <p class="lp-nice-hero__lead">
                    Скопируйте ссылку ниже и добавьте её в Happ. Мы также отправили её на <?php echo e($buyer?->email); ?>. В Happ по кнопке ⓘ можно войти в личный кабинет без пароля.
                </p>
            <?php elseif($isPaid): ?>
                <span class="lp-nice-hero__kicker">
                    <span class="lp-nice-hero__kicker-dot" aria-hidden="true"></span>
                    Оплата принята
                </span>
                <h1 class="lp-nice-hero__title">
                    Создаём <span class="lp-nice-hero__title-em">подписку</span>
                </h1>
                <p class="lp-nice-hero__lead">
                    Платёж прошёл — подписка появится через несколько секунд. Страница обновится автоматически.
                </p>
            <?php else: ?>
                <span class="lp-nice-hero__kicker">
                    <span class="lp-nice-hero__kicker-dot" aria-hidden="true"></span>
                    Ожидание
                </span>
                <h1 class="lp-nice-hero__title">
                    Ждём <span class="lp-nice-hero__title-em">оплату</span>
                </h1>
                <p class="lp-nice-hero__lead">
                    Если вы уже оплатили — подождите минуту и обновите страницу. Иначе вернитесь к оплате.
                </p>
            <?php endif; ?>
        </section>

        <?php if($isPaid && $subscription !== null): ?>
            <div class="lp-buy-done-box">
                <div class="lp-buy-done-box__label">Ссылка подписки</div>
                <code class="lp-buy-done-box__url" id="lp-buy-sub-url"><?php echo e($subUrl); ?></code>
                <div class="lp-buy-done-actions">
                    <button type="button" class="lp-buy-done-btn" id="lp-buy-copy-sub">Скопировать</button>
                    <?php if($cabinetLoginUrl): ?>
                        <a href="<?php echo e($cabinetLoginUrl); ?>" class="lp-buy-done-btn lp-buy-done-btn--secondary">Войти в кабинет</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if($buyer !== null && $plainPassword !== null): ?>
                <div class="lp-buy-done-box">
                    <div class="lp-buy-done-box__label">Данные для входа на сайте</div>
                    <div class="lp-buy-done-creds">
                        Логин: <?php echo e($buyer->email); ?><br>
                        Пароль: <?php echo e($plainPassword); ?>

                    </div>
                    <p class="lp-buy-modal__hint" style="text-align:left;margin-top:10px;">
                        Сохраните пароль — он показывается один раз. В Happ можно войти в кабинет по кнопке ⓘ без пароля.
                    </p>
                </div>
            <?php endif; ?>

            <div class="lp-buy-done-box">
                <div class="lp-buy-done-box__label">Happ</div>
                <p class="lp-nice-hero__lead" style="margin-bottom:12px;">
                    Скопируйте ссылку подписки и вставьте в Happ, либо откройте приложение и добавьте подписку вручную.
                </p>
                <div class="lp-buy-done-actions">
                    <?php if($iosAppUrl): ?>
                        <a href="<?php echo e($iosAppUrl); ?>" target="_blank" rel="noopener noreferrer" class="lp-buy-done-btn lp-buy-done-btn--secondary">iOS</a>
                    <?php endif; ?>
                    <?php if($androidAppUrl): ?>
                        <a href="<?php echo e($androidAppUrl); ?>" target="_blank" rel="noopener noreferrer" class="lp-buy-done-btn lp-buy-done-btn--secondary">Android</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(! empty($referralLink)): ?>
                <?php echo $__env->make('partials.referral-share-promo', ['referralLink' => $referralLink], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>

        <?php elseif($isPaid): ?>
            <div class="lp-buy-wait" id="lp-buy-done-wait">
                Подготавливаем подписку…
            </div>
        <?php else: ?>
            <div class="lp-buy-wait">
                <a href="<?php echo e(route('quick_buy.show')); ?>" class="lp-buy-done-btn">Вернуться к оплате</a>
            </div>
        <?php endif; ?>

        <footer class="lp-footer-mock" style="margin-top:40px;">
            <div class="footer-bottom">
                <span>Нужна помощь?</span>
                <span><a href="<?php echo e($tg); ?>" target="_blank" rel="noopener noreferrer" class="text-inherit underline underline-offset-2">Telegram</a></span>
            </div>
        </footer>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php if(!empty($shouldPoll)): ?>
<script>
(function () {
    var orderId = <?php echo json_encode($order->order_id, 15, 512) ?>;
    var claim = <?php echo json_encode($claimToken, 15, 512) ?>;
    var url = <?php echo json_encode(url('/buy/status'), 15, 512) ?> + '/' + encodeURIComponent(orderId) + '?claim=' + encodeURIComponent(claim);
    var timer = setInterval(function () {
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'paid' && data.subscriptionUrl) {
                    clearInterval(timer);
                    window.location.reload();
                } else if (data.status === 'declined') {
                    clearInterval(timer);
                    window.location.reload();
                }
            })
            .catch(function () {});
    }, 2500);
})();
</script>
<?php endif; ?>
<?php if($isPaid && $subscription !== null): ?>
<script>
(function () {
    var btn = document.getElementById('lp-buy-copy-sub');
    var urlEl = document.getElementById('lp-buy-sub-url');
    if (!btn || !urlEl) return;
    btn.addEventListener('click', async function () {
        var text = urlEl.textContent || '';
        try {
            await navigator.clipboard.writeText(text);
            btn.textContent = 'Скопировано';
            setTimeout(function () { btn.textContent = 'Скопировать'; }, 1600);
        } catch (_) {
            alert('Не удалось скопировать. Выделите ссылку вручную.');
        }
    });
})();
</script>
<?php endif; ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('views2::layouts.marketing', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\kfcku\Desktop\nadezhda.space\mainServer\resources\views/quick-buy/done.blade.php ENDPATH**/ ?>
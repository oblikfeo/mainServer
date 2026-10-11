<?php
    $brand = config('marketing.brand_name', 'Надежда');
    $tg = config('marketing.telegram_support_url', config('marketing.telegram_url', 'https://t.me/nadezhda_tehsup'));
?>

<?php $__env->startSection('title', $brand.' — быстрая оплата'); ?>
<?php $__env->startSection('meta_description', 'Выберите срок и число устройств и оплатите подписку за пару кликов — без регистрации. Оплата картой или СБП на защищённой странице платёжной системы.'); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('views2::partials.lp-f1-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('views2::partials.lp-header-views2-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('views2::partials.lp-pricing-support-footer-mock-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('views2::partials.lp-views2-responsive-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('cabinet.nice.partials.nice-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('quick-buy.partials.buy-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="lp-f1 lp-f1-body">
    <div class="lp-container">
        <header class="lp-header lp-header-v2 lp-header--drawer">
            <div class="lp-header__bar">
                <a href="<?php echo e(url('/')); ?>" class="lp-brand-line" style="text-decoration:none;">
                    <span class="lp-logo-heavy"><?php echo e(mb_strtoupper($brand, 'UTF-8')); ?></span>
                    <span class="lp-logo-vpn">VPN</span>
                </a>
                <nav class="lp-header__nav" id="lp-main-nav" aria-label="Быстрые ссылки">
                    <a href="<?php echo e(url('/')); ?>">Главная</a>
                    <a href="#tarify">Цены</a>
                    <a href="<?php echo e($tg); ?>" target="_blank" rel="noopener noreferrer">Поддержка</a>
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
                <?php if(auth()->guard()->check()): ?>
                    <a href="<?php echo e(route('dashboard')); ?>" class="lp-header-cta">Кабинет</a>
                <?php else: ?>
                    <a href="<?php echo e(route('login')); ?>" class="lp-header-cta">Войти</a>
                <?php endif; ?>
            </div>
        </header>

        <section class="lp-nice-hero">
            <span class="lp-nice-hero__kicker">
                <span class="lp-nice-hero__kicker-dot" aria-hidden="true"></span>
                Без регистрации
            </span>
            <h1 class="lp-nice-hero__title">
                Оплата в <span class="lp-nice-hero__title-em">2 клика</span>
            </h1>
            <p class="lp-nice-hero__lead">
                Выберите срок и число устройств, укажите почту и перейдите к оплате. Аккаунт создадим автоматически — подписку можно сразу добавить в Happ.
            </p>
        </section>

        <section id="tarify" class="lp-pricing lp-pricing-mock">
            <div class="lp-pricing-head-mock">
                <h2 class="lp-section-title">Выберите подписку</h2>
            </div>
            <div class="pricing-container">
                <?php echo $__env->make('quick-buy.partials.pricing-buy-plaques', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
            <div class="lp-pricing-guarantees">
                <?php echo $__env->make('views2::partials.pricing-payment-notes', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </section>

        <footer class="lp-footer-mock">
            <div class="footer-bottom">
                <span>&copy; <?php echo e(date('Y')); ?> <?php echo e(mb_strtoupper($brand, 'UTF-8')); ?></span>
                <span>
                    Оплачивая подписку, вы соглашаетесь с
                    <a href="<?php echo e(route('agreement')); ?>" class="text-inherit underline underline-offset-2">публичной офертой</a>.
                </span>
            </div>
        </footer>
    </div>
</div>

<div class="lp-buy-modal" id="lp-buy-email-modal" role="dialog" aria-modal="true" aria-labelledby="lp-buy-email-title" aria-hidden="true">
    <div class="lp-buy-modal__panel">
        <button type="button" class="lp-buy-modal__close" id="lp-buy-email-close" aria-label="Закрыть">&times;</button>
        <span class="lp-buy-modal__kicker">
            <span class="lp-buy-modal__kicker-dot" aria-hidden="true"></span>
            Перед оплатой
        </span>
        <h2 class="lp-buy-modal__title" id="lp-buy-email-title">
            Укажите <span class="lp-buy-modal__title-em">почту</span>
        </h2>
        <p class="lp-buy-modal__sub" id="lp-buy-email-desc">Отправим ссылку подписки после оплаты — можно сразу добавить в Happ.</p>
        <div class="lp-buy-modal__amount-wrap">
            <span class="lp-buy-modal__amount-label">К оплате</span>
            <p class="lp-buy-modal__amount" id="lp-buy-email-amount"></p>
        </div>
        <form id="lp-buy-email-form" class="lp-buy-email-modal-form">
            <label class="lp-buy-modal__field-label" for="lp-buy-email-input">Email</label>
            <input type="email" id="lp-buy-email-input" name="email" required autocomplete="email" placeholder="your@email.com">
            <p class="lp-buy-modal__status lp-buy-modal__status--error" id="lp-buy-email-error" hidden></p>
            <button type="submit" class="lp-buy-pay-btn" id="lp-buy-email-submit">Перейти к оплате</button>
        </form>
        <p class="lp-buy-modal__hint">После оплаты вы вернётесь на наш сайт — там будет ссылка подписки. Дубликат отправим на указанный email.</p>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php echo $__env->make('quick-buy.partials.buy-script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('views2::layouts.marketing', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\kfcku\Desktop\nadezhda.space\mainServer\resources\views/quick-buy/index.blade.php ENDPATH**/ ?>
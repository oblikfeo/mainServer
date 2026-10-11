

<?php
    $brand = config('marketing.brand_name', 'Надежда');
    $tg = config('marketing.telegram_support_url', config('marketing.telegram_url', 'https://t.me/nadezhda_tehsup'));
?>

<?php $__env->startSection('title', $brand.' — стабильный интернет без зависаний'); ?>
<?php $__env->startSection('meta_description', 'Сервис для стабильной работы интернет-соединения и доступа к онлайн-сервисам. Быстро, просто и без лишних настроек.'); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('views2::partials.lp-f1-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('views2::partials.lp-header-views2-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('views2::partials.lp-hero-marquee-mock-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('views2::partials.lp-features-mock-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('views2::partials.lp-pricing-support-footer-mock-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('views2::partials.lp-views2-responsive-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="lp-f1 lp-f1-body">
    <div class="lp-container">
        <header class="lp-header lp-header-v2 lp-header--drawer">
            <div class="lp-header__bar">
                <div class="lp-brand-line">
                    <span class="lp-logo-heavy"><?php echo e(mb_strtoupper($brand, 'UTF-8')); ?></span>
                    <span class="lp-logo-vpn">VPN</span>
                </div>
                <nav class="lp-header__nav" id="lp-main-nav" aria-label="Разделы страницы">
                    <a href="#features">О сервисе</a>
                    <a href="#tarify">Тарифы</a>
                    <a href="#support">Поддержка</a>
                </nav>
                <button
                    type="button"
                    class="lp-nav-toggle"
                    id="lp-nav-toggle"
                    aria-expanded="false"
                    aria-controls="lp-main-nav"
                    aria-label="Открыть меню разделов"
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

        <section class="hero">
            <div class="hero-content">
                <div class="trust-badge">Сделали как для своих</div>
                <h1 class="hero-title">
                    Интернет,<br>
                    который <span class="hero-title-em">работает</span>
                </h1>
                <p class="hero-description">
                    Стабильное соединение, быстрая загрузка и комфортная работа с привычными онлайн-сервисами. Без лишних настроек.
                </p>
                <div class="hero-buttons">
                    <a href="<?php echo e(auth()->check() ? route('dashboard') : route('register')); ?>" class="btn-cta btn-cta--primary">
                        Попробовать бесплатно (8ч)
                    </a>
                    <a href="<?php echo e(route('quick_buy.show')); ?>" class="btn-cta">
                        Купить в 3 клика
                    </a>
                </div>
                <p class="hero-note">Без привязки карты. Никаких скрытых платежей.</p>
            </div>
            <div class="hero-img">
                <div class="sticker">
                    СТАБИЛЬНО<br>24/7
                </div>
            </div>
        </section>

        <div class="marquee" aria-hidden="true">
            <div class="marquee-track">
                <span class="marquee-segment">&nbsp;&nbsp;★ СТАБИЛЬНЫЙ ИНТЕРНЕТ ★ БЕЗ ЗАВИСАНИЙ ★ БЫСТРАЯ ЗАГРУЗКА ★ ПОДДЕРЖКА 24/7 ★</span>
                <span class="marquee-segment">&nbsp;&nbsp;★ СТАБИЛЬНЫЙ ИНТЕРНЕТ ★ БЕЗ ЗАВИСАНИЙ ★ БЫСТРАЯ ЗАГРУЗКА ★ ПОДДЕРЖКА 24/7 ★</span>
            </div>
        </div>

        <section class="section-padding lp-features-section" id="features">
            <div class="section-header">
                <h2 class="section-title">КОРОТКО О ГЛАВНОМ</h2>
            </div>

            <p class="about-text">
                Мы создали этот сервис для наших близких и друзей, когда заметили, что интернет стал нестабильным: сообщения не доходят, страницы долго загружаются, связь прерывается. Наша задача — вернуть стабильность и удобство повседневного использования сети.
            </p>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon" aria-hidden="true">🛋️</div>
                    <h3>Комфорт каждый день</h3>
                    <p>Сервис работает в фоне и не требует постоянного переключения. Всё функционирует так, как вы привыкли.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" aria-hidden="true">⚡</div>
                    <h3>Быстрая работа</h3>
                    <p>Видео, сайты и приложения загружаются без задержек и подвисаний.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" aria-hidden="true">🤖</div>
                    <h3>Современные сервисы</h3>
                    <p>Поддержка стабильной работы популярных онлайн-платформ и инструментов, включая нейросети.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" aria-hidden="true">🔒</div>
                    <h3>Надёжное соединение</h3>
                    <p>Стабильная работа даже при нестабильном качестве сети или помехах.</p>
                </div>
            </div>
        </section>

        <section id="tarify" class="lp-pricing lp-pricing-mock">
            <div class="lp-pricing-head-mock">
                <h2 class="lp-section-title">Понятные цены</h2>
            </div>
            <div class="pricing-container">
                <?php echo $__env->make('views2::partials.pricing-mock-plaques', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
            <div class="lp-pricing-cta-wrap">
                <a href="<?php echo e(route('cabinet.payment')); ?>" class="btn-cta btn-cta--primary">Подключиться</a>
                <a href="<?php echo e(route('quick_buy.show')); ?>" class="btn-cta">Купить без регистрации</a>
            </div>
            <div class="lp-pricing-guarantees">
                <?php echo $__env->make('views2::partials.pricing-payment-notes', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </section>

        <section id="support" class="lp-support-section-mock section-padding">
            <div class="support-content">
                <h2 class="section-title">ПОДДЕРЖКА</h2>
                <p class="support-text">
                    У нас нет роботов. Вам ответит живой человек, который поможет с настройкой и ответит на любые вопросы.
                </p>
                <div class="support-badge">
                    <span class="support-time">~7 минут</span>
                    <span class="support-label">среднее время ответа</span>
                </div>
                <a href="<?php echo e($tg); ?>" target="_blank" rel="noopener noreferrer" class="btn-cta lp-support-tg-btn">
                    Написать в Telegram
                </a>
            </div>
        </section>

        <footer class="lp-footer-mock">
            <div class="footer-intro">
                <div class="footer-logo"><?php echo e(mb_strtoupper($brand, 'UTF-8')); ?></div>
                <p class="footer-description">
                    Пользователь самостоятельно определяет цели использования сервиса и несёт ответственность за соблюдение применимого законодательства.
                </p>
            </div>
            <div class="footer-links-cluster">
                <div class="footer-links">
                    <h4>Навигация</h4>
                    <ul>
                        <li><a href="#features">О сервисе</a></li>
                        <li><a href="#tarify">Тарифы</a></li>
                        <li><a href="#support">Поддержка</a></li>
                    </ul>
                </div>
                <div class="footer-links footer-docs">
                    <h4>Документы</h4>
                    <ul>
                        <li><a href="<?php echo e(route('agreement')); ?>">Публичная оферта</a></li>
                        <li><a href="<?php echo e(route('privacy')); ?>">Политика конфиденциальности</a></li>
                        <li><a href="<?php echo e(route('terms')); ?>">Пользовательское соглашение</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; <?php echo e(date('Y')); ?> <?php echo e(mb_strtoupper($brand, 'UTF-8')); ?></span>
                <span>Стабильный интернет без зависаний</span>
            </div>
        </footer>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    var toggle = document.getElementById('lp-nav-toggle');
    var nav = document.getElementById('lp-main-nav');
    if (!toggle || !nav) return;
    function setOpen(open) {
        nav.classList.toggle('lp-header__nav--open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Закрыть меню разделов' : 'Открыть меню разделов');
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

<?php echo $__env->make('views2::layouts.marketing', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\kfcku\Desktop\nadezhda.space\mainServer\resources\views2/home.blade.php ENDPATH**/ ?>
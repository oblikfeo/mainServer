<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
        <?php echo $__env->make('partials.yandex-metrika', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php
            $brand = config('marketing.brand_name', 'Надежда');
            $rn = request()->route()?->getName();
            $pageTitle = match ($rn) {
                'login' => 'Вход',
                'register' => 'Регистрация',
                'promo.register' => 'Регистрация',
                'partner.reset.register' => 'Reset',
                'password.request' => 'Сброс пароля',
                'password.reset' => 'Новый пароль',
                'verification.notice' => 'Подтвердите email',
                'password.confirm' => 'Подтверждение',
                default => 'Аккаунт',
            };
        ?>
        <title><?php echo e($brand); ?> — <?php echo e($pageTitle); ?></title>
        <?php echo $__env->make('partials.favicon', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Space+Grotesk:wght@500;600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        <?php echo $__env->make('partials.lp-f1-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </head>
    <body class="m-0 p-0">
        <div class="lp-f1 lp-f1-body lp-f1-auth">
            <div class="lp-container lp-container--narrow">
                <div class="lp-header">
                    <a href="<?php echo e(url('/')); ?>" class="lp-logo lp-cabinet-header__brand"><?php echo e($brand); ?></a>
                    <?php if(in_array($rn, ['register', 'promo.register', 'password.request', 'password.reset'], true)): ?>
                        <a href="<?php echo e(route('login')); ?>" class="lp-login-btn">Вход</a>
                    <?php elseif($rn === 'login'): ?>
                        <a href="<?php echo e(route('register')); ?>" class="lp-login-btn">Регистрация</a>
                    <?php else: ?>
                        <a href="<?php echo e(route('login')); ?>" class="lp-login-btn">Вход</a>
                    <?php endif; ?>
                </div>

                <div class="lp-auth-panel">
                    <?php echo e($slot); ?>

                </div>

                <div class="lp-auth-footer">
                    <a href="<?php echo e(url('/')); ?>">На главную</a>
                </div>
            </div>
        </div>
    </body>
</html>
<?php /**PATH C:\Users\kfcku\Desktop\nadezhda.space\mainServer\resources\views/layouts/guest.blade.php ENDPATH**/ ?>
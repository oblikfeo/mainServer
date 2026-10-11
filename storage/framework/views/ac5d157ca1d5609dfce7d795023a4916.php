<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
        <?php echo $__env->make('partials.yandex-metrika', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <title><?php echo $__env->yieldContent('title', config('marketing.brand_name', 'Надежда')); ?></title>
        <meta name="description" content="<?php echo $__env->yieldContent('meta_description', ''); ?>">
        <meta property="og:title" content="<?php echo $__env->yieldContent('title', config('marketing.brand_name', 'Надежда')); ?>">
        <meta property="og:description" content="<?php echo $__env->yieldContent('meta_description', ''); ?>">
        <meta property="og:type" content="website">
        <?php echo $__env->make('partials.favicon', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400;1,700&family=Space+Grotesk:wght@500;600;700&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        <?php echo $__env->yieldPushContent('styles'); ?>
    </head>
    <body class="font-sans text-slate-900 antialiased bg-slate-50">
        <?php echo $__env->yieldContent('content'); ?>
        <?php echo $__env->yieldPushContent('scripts'); ?>
    </body>
</html>
<?php /**PATH C:\Users\kfcku\Desktop\nadezhda.space\mainServer\resources\views2/layouts/marketing.blade.php ENDPATH**/ ?>
<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $brand = config('marketing.brand_name', 'Надежда');
    $supportTgUrl = config('marketing.telegram_support_url', 'https://t.me/nadezhda_tehsup');
    $newsTgUrl = 'https://t.me/Nadezhda_VPN';
?>

<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
        <?php echo $__env->make('partials.yandex-metrika', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <title><?php echo e($brand); ?> — личный кабинет</title>
        <?php echo $__env->make('partials.favicon', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        <?php echo $__env->make('partials.lp-f1-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </head>
    <body class="m-0 p-0">
        <div class="lp-f1 lp-f1-body lp-f1-cabinet" x-data="{ mobileNav: false }">
            <div class="lp-container lp-container--cabinet">
                <header class="lp-header lp-cabinet-header">
                    <div class="lp-cabinet-header__row">
                        <a href="<?php echo e(url('/')); ?>" class="lp-logo lp-cabinet-header__brand"><?php echo e($brand); ?></a>

                        <nav class="lp-cab-nav" aria-label="Разделы кабинета">
                            <a
                                href="<?php echo e(route('dashboard')); ?>"
                                class="lp-cab-nav__link <?php echo e(request()->routeIs('dashboard') ? 'lp-cab-nav__link--active' : ''); ?>"
                            >Моя подписка</a>
                            <a
                                href="<?php echo e(route('cabinet.profile')); ?>"
                                class="lp-cab-nav__link <?php echo e(request()->routeIs('cabinet.profile') ? 'lp-cab-nav__link--active' : ''); ?>"
                            >Профиль</a>
                            <a
                                href="<?php echo e(route('cabinet.referral')); ?>"
                                class="lp-cab-nav__link <?php echo e(request()->routeIs('cabinet.referral') ? 'lp-cab-nav__link--active' : ''); ?>"
                            >Реферальная программа</a>
                            <a
                                href="<?php echo e(route('cabinet.settings')); ?>"
                                class="lp-cab-nav__link <?php echo e(request()->routeIs('cabinet.settings') ? 'lp-cab-nav__link--active' : ''); ?>"
                            >Устройства</a>
                            <a
                                href="<?php echo e(route('cabinet.payment')); ?>"
                                class="lp-cab-nav__link <?php echo e(request()->routeIs('cabinet.payment') ? 'lp-cab-nav__link--active' : ''); ?>"
                            >Оплата</a>
                            <a
                                href="<?php echo e(route('cabinet.purchases')); ?>"
                                class="lp-cab-nav__link <?php echo e(request()->routeIs('cabinet.purchases') ? 'lp-cab-nav__link--active' : ''); ?>"
                            >История покупок</a>
                            <a
                                href="<?php echo e($supportTgUrl); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="lp-cab-nav__link"
                            >Поддержка</a>
                        </nav>

                        <div class="lp-cabinet-header__tools">
                            <button
                                type="button"
                                class="lp-header-burger md:hidden"
                                @click="mobileNav = true"
                                aria-expanded="false"
                                :aria-expanded="mobileNav"
                            >
                                <span class="sr-only">Открыть меню</span>
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>

                            <div class="hidden md:block">
                                <?php if (isset($component)) { $__componentOriginaldf8083d4a852c446488d8d384bbc7cbe = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dropdown','data' => ['align' => 'right','width' => '48','contentClasses' => 'py-1 bg-white lp-dropdown-panel']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['align' => 'right','width' => '48','content-classes' => 'py-1 bg-white lp-dropdown-panel']); ?>
                                     <?php $__env->slot('trigger', null, []); ?> 
                                        <button type="button" class="lp-user-trigger">
                                            <span class="max-w-[10rem] truncate"><?php echo e(Auth::user()->name); ?></span>
                                        </button>
                                     <?php $__env->endSlot(); ?>
                                     <?php $__env->slot('content', null, []); ?> 
                                        <div class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-600 border-b-2 border-black truncate"><?php echo e(Auth::user()->email); ?></div>
                                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                                            <?php echo csrf_field(); ?>
                                            <?php if (isset($component)) { $__componentOriginal68cb1971a2b92c9735f83359058f7108 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal68cb1971a2b92c9735f83359058f7108 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dropdown-link','data' => ['href' => route('logout'),'onclick' => 'event.preventDefault(); this.closest(\'form\').submit();']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dropdown-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('logout')),'onclick' => 'event.preventDefault(); this.closest(\'form\').submit();']); ?>
                                                Выйти
                                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal68cb1971a2b92c9735f83359058f7108)): ?>
<?php $attributes = $__attributesOriginal68cb1971a2b92c9735f83359058f7108; ?>
<?php unset($__attributesOriginal68cb1971a2b92c9735f83359058f7108); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal68cb1971a2b92c9735f83359058f7108)): ?>
<?php $component = $__componentOriginal68cb1971a2b92c9735f83359058f7108; ?>
<?php unset($__componentOriginal68cb1971a2b92c9735f83359058f7108); ?>
<?php endif; ?>
                                        </form>
                                     <?php $__env->endSlot(); ?>
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe)): ?>
<?php $attributes = $__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe; ?>
<?php unset($__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldf8083d4a852c446488d8d384bbc7cbe)): ?>
<?php $component = $__componentOriginaldf8083d4a852c446488d8d384bbc7cbe; ?>
<?php unset($__componentOriginaldf8083d4a852c446488d8d384bbc7cbe); ?>
<?php endif; ?>
                            </div>
                        </div>
                    </div>
                </header>

                <div class="lp-cab-marquee" role="region" aria-label="Новости">
                    <a href="<?php echo e($newsTgUrl); ?>" target="_blank" rel="noopener noreferrer" class="lp-cab-marquee__link" aria-label="Новости в Telegram">
                        <span class="lp-cab-marquee__viewport">
                            <span class="lp-cab-marquee__track" aria-hidden="true">
                                <?php
                                    $tgIcon = '<svg class="lp-cab-marquee__tg" viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>';
                                    $d = '<span class="lp-cab-marquee__dot">•</span>';
                                    $item = fn($t) => $d.$tgIcon.$d.'<span>'.$t.'</span>';
                                    $segment = $item('@Nadezhda_VPN').$item('Поддержка').$item('Новости').$item('Актуальная информация').$item('Акции');
                                ?>
                                <span class="lp-cab-marquee__segment"><?php echo $segment; ?><?php echo $segment; ?></span>
                                <span class="lp-cab-marquee__segment"><?php echo $segment; ?><?php echo $segment; ?></span>
                            </span>
                            <span class="lp-cab-marquee__sr">@Nadezhda_VPN · Поддержка · Новости · Актуальная информация · Акции</span>
                        </span>
                    </a>
                </div>

                <div
                    x-show="mobileNav"
                    x-transition.opacity
                    class="fixed inset-0 z-[60] md:hidden"
                    style="display: none;"
                    x-cloak
                >
                    <div class="absolute inset-0 bg-slate-900/50" @click="mobileNav = false" aria-hidden="true"></div>
                    <div
                        x-show="mobileNav"
                        x-transition:enter="transition transform ease-out duration-200"
                        x-transition:enter-start="-translate-x-full"
                        x-transition:enter-end="translate-x-0"
                        x-transition:leave="transition transform ease-in duration-150"
                        x-transition:leave-start="translate-x-0"
                        x-transition:leave-end="-translate-x-full"
                        class="absolute left-0 top-0 bottom-0 w-[min(100%,20rem)] bg-white lp-drawer flex flex-col"
                        @click.away="mobileNav = false"
                    >
                        <div class="flex items-center justify-between p-4 border-b-4 border-black">
                            <span class="font-black uppercase text-sm tracking-tight"><?php echo e($brand); ?></span>
                            <button type="button" class="lp-header-burger" @click="mobileNav = false" aria-label="Закрыть">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <nav class="flex-1 overflow-y-auto p-3 lp-drawer-nav" aria-label="Мобильное меню">
                            <a href="<?php echo e(route('dashboard')); ?>" @click="mobileNav = false" class="<?php echo e(request()->routeIs('dashboard') ? 'lp-cab-nav__link--active' : ''); ?>">Моя подписка</a>
                            <a href="<?php echo e(route('cabinet.profile')); ?>" @click="mobileNav = false" class="<?php echo e(request()->routeIs('cabinet.profile') ? 'lp-cab-nav__link--active' : ''); ?>">Профиль</a>
                            <a href="<?php echo e(route('cabinet.referral')); ?>" @click="mobileNav = false" class="<?php echo e(request()->routeIs('cabinet.referral') ? 'lp-cab-nav__link--active' : ''); ?>">Реферальная программа</a>
                            <a href="<?php echo e(route('cabinet.settings')); ?>" @click="mobileNav = false" class="<?php echo e(request()->routeIs('cabinet.settings') ? 'lp-cab-nav__link--active' : ''); ?>">Устройства</a>
                            <a href="<?php echo e(route('cabinet.payment')); ?>" @click="mobileNav = false" class="<?php echo e(request()->routeIs('cabinet.payment') ? 'lp-cab-nav__link--active' : ''); ?>">Оплата</a>
                            <a href="<?php echo e(route('cabinet.purchases')); ?>" @click="mobileNav = false" class="<?php echo e(request()->routeIs('cabinet.purchases') ? 'lp-cab-nav__link--active' : ''); ?>">История покупок</a>
                            <a href="<?php echo e($supportTgUrl); ?>" target="_blank" rel="noopener noreferrer" @click="mobileNav = false">Поддержка</a>
                        </nav>
                        <div class="p-4 border-t-4 border-black space-y-3">
                            <p class="text-xs font-bold uppercase text-slate-600 truncate"><?php echo e(Auth::user()->email); ?></p>
                            <form method="POST" action="<?php echo e(route('logout')); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="w-full lp-login-btn" style="width:100%;text-align:center;">Выйти</button>
                            </form>
                        </div>
                    </div>
                </div>

                <main class="lp-cabinet-main">
                    <?php echo e($slot); ?>

                </main>
            </div>
            <?php if(Auth::user()?->promo_welcome_pending): ?>
                <?php
                    $promoKey = strtoupper((string) Auth::user()->registration_promo_code);
                    $promoDays = (int) (config('promo.codes.'.$promoKey.'.trial_days') ?: 7);
                ?>
                <div class="lp-promo-welcome" role="dialog" aria-modal="true" aria-labelledby="lp-promo-welcome-title">
                    <div class="lp-promo-welcome__inner">
                        <p id="lp-promo-welcome-title" class="lp-promo-welcome__title">
                            Вам доступна тестовая подписка на <?php echo e($promoDays); ?> <?php echo e($promoDays === 1 ? 'день' : 'дней'); ?>

                        </p>
                        <div class="lp-promo-welcome__actions">
                            <form method="POST" action="<?php echo e(route('cabinet.promo_welcome')); ?>">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="skip">
                                <button type="submit" class="lp-promo-welcome__btn lp-promo-welcome__btn--skip">Пропустить</button>
                            </form>
                            <form method="POST" action="<?php echo e(route('cabinet.promo_welcome')); ?>">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="claim">
                                <button type="submit" class="lp-promo-welcome__btn lp-promo-welcome__btn--claim">Забрать</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <style>
            [x-cloak]{display:none!important}
            .lp-promo-welcome {
                position: fixed;
                inset: 0;
                z-index: 200;
                background-color: #f4f4f4;
                background-image: radial-gradient(#d1d1d1 1px, transparent 1px);
                background-size: 20px 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                color: #111;
            }
            .lp-promo-welcome__inner {
                max-width: 32rem;
                width: 100%;
            }
            .lp-promo-welcome__title {
                font-size: clamp(1.35rem, 4vw, 2.1rem);
                font-weight: 800;
                text-align: center;
                margin: 0 0 2rem;
                line-height: 1.25;
                color: #FF4500;
            }
            .lp-promo-welcome__actions {
                display: flex;
                gap: 1rem;
                justify-content: center;
                flex-wrap: wrap;
            }
            .lp-promo-welcome__btn {
                min-width: 10rem;
                padding: 0.85rem 1.4rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: .04em;
                border: 3px solid #111;
                cursor: pointer;
            }
            .lp-promo-welcome__btn--skip {
                background: transparent;
                color: #111;
                border-color: #111;
            }
            .lp-promo-welcome__btn--claim {
                background: #FF4500;
                color: #fff;
                border-color: #111;
            }
        </style>
    </body>
</html>
<?php /**PATH C:\Users\kfcku\Desktop\nadezhda.space\mainServer\resources\views/layouts/cabinet.blade.php ENDPATH**/ ?>
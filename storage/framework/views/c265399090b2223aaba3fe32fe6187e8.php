<?php
    $verifyModalOpen = $errors->has('code') || $errors->has('email_code') || session('status') === 'email-code-sent';
    $accordionOpenProfile = $errors->has('name') || $errors->has('email')
        || session('status') === 'profile-updated'
        || session('status') === 'verification-link-sent';
    $accordionOpenPassword = $errors->getBag('updatePassword')->isNotEmpty()
        || session('status') === 'password-updated';
    $accordionOpenDelete = $errors->getBag('userDeletion')->isNotEmpty();
?>

<?php if (isset($component)) { $__componentOriginalf89371928a89b76038cd3904d0f3e1f4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf89371928a89b76038cd3904d0f3e1f4 = $attributes; } ?>
<?php $component = App\View\Components\CabinetLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('cabinet-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\CabinetLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <div class="max-w-3xl mx-auto">
        <h1 class="lp-page-title">Профиль</h1>

        <?php if(! $user->hasVerifiedEmail()): ?>
            <div class="lp-profile-block">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 mb-0">Данные аккаунта</h2>
                <div
                    x-data="emailVerifyProfile(<?php echo \Illuminate\Support\Js::from([
                        'sendUrl' => route('cabinet.email_code.send'),
                        'modalName' => 'verify-email-by-code',
                        'csrfToken' => csrf_token(),
                    ])->toHtml() ?>)"
                >
                    <dl class="lp-dl-grid lp-dl-grid--account">
                        <div>
                            <dt>Имя</dt>
                            <dd><?php echo e($user->name); ?></dd>
                        </div>
                        <div>
                            <dt>Эл. почта</dt>
                            <dd class="font-mono break-all"><?php echo e($user->email); ?></dd>
                        </div>
                        <div>
                            <dt>Дата регистрации</dt>
                            <dd class="tabular-nums"><?php echo e($user->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—'); ?></dd>
                        </div>
                        <div>
                            <dt class="sr-only">Подтвердить адрес электронной почты</dt>
                            <dd class="lp-dl-grid__action">
                                <button
                                    type="button"
                                    class="lp-account-verify-btn"
                                    x-bind:disabled="sending"
                                    x-on:click="sendCode()"
                                >
                                    <span x-show="!sending">Подтвердить почту</span>
                                    <span x-show="sending" x-cloak>Отправляем…</span>
                                </button>
                                <p
                                    x-show="sendError !== ''"
                                    x-cloak
                                    x-text="sendError"
                                    class="mt-2 text-xs font-bold text-red-700 border-2 border-black bg-red-50 px-2 py-1"
                                ></p>
                                <p
                                    x-show="sendInfo !== ''"
                                    x-cloak
                                    x-text="sendInfo"
                                    class="mt-2 text-xs font-bold text-amber-900 border-2 border-black bg-amber-50 px-2 py-1"
                                ></p>
                            </dd>
                        </div>
                    </dl>

                    <?php if (isset($component)) { $__componentOriginal9f64f32e90b9102968f2bc548315018c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9f64f32e90b9102968f2bc548315018c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.modal','data' => ['name' => 'verify-email-by-code','show' => $verifyModalOpen,'focusable' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'verify-email-by-code','show' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($verifyModalOpen),'focusable' => true]); ?>
                        <div class="p-6">
                            <h2 class="text-lg font-medium text-gray-900">
                                Код из письма
                            </h2>

                            <p class="mt-3 text-sm font-semibold text-gray-700">Письмо отправлено на</p>
                            <div class="lp-verify-email-modal-address"><?php echo e($user->email); ?></div>

                            <p class="mt-4 text-sm text-gray-600">Введите 4 цифры из письма.</p>

                            <form method="POST" action="<?php echo e(route('cabinet.email_code.verify')); ?>" class="mt-4 space-y-4">
                                <?php echo csrf_field(); ?>
                                <div>
                                    <label class="block text-sm font-bold uppercase tracking-wide text-slate-600">Код</label>
                                    <input
                                        name="code"
                                        inputmode="numeric"
                                        autocomplete="one-time-code"
                                        maxlength="4"
                                        class="mt-1 block w-full"
                                        value="<?php echo e(old('code')); ?>"
                                    />
                                    <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="mt-2 text-sm text-red-600"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <button type="submit">Подтвердить</button>
                                    <?php if (isset($component)) { $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.secondary-button','data' => ['type' => 'button','class' => 'lp-secondary-outline','xOn:click' => '$dispatch(\'close\')']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('secondary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'button','class' => 'lp-secondary-outline','x-on:click' => '$dispatch(\'close\')']); ?>
                                        Закрыть
                                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $attributes = $__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__attributesOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af)): ?>
<?php $component = $__componentOriginal3b0e04e43cf890250cc4d85cff4d94af; ?>
<?php unset($__componentOriginal3b0e04e43cf890250cc4d85cff4d94af); ?>
<?php endif; ?>
                                </div>
                            </form>
                        </div>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $attributes = $__attributesOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__attributesOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9f64f32e90b9102968f2bc548315018c)): ?>
<?php $component = $__componentOriginal9f64f32e90b9102968f2bc548315018c; ?>
<?php unset($__componentOriginal9f64f32e90b9102968f2bc548315018c); ?>
<?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="lp-profile-block">
                <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 mb-0">Данные аккаунта</h2>
                <dl class="lp-dl-grid lp-dl-grid--account">
                    <div>
                        <dt>Имя</dt>
                        <dd><?php echo e($user->name); ?></dd>
                    </div>
                    <div>
                        <dt>Эл. почта</dt>
                        <dd class="font-mono break-all"><?php echo e($user->email); ?></dd>
                    </div>
                    <div>
                        <dt>Дата регистрации</dt>
                        <dd class="tabular-nums"><?php echo e($user->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—'); ?></dd>
                    </div>
                    <div>
                        <dt>Статус</dt>
                        <dd>
                            <span class="lp-badge-pill lp-badge-pill--ok">Почта подтверждена</span>
                        </dd>
                    </div>
                </dl>
            </div>
        <?php endif; ?>

        <?php echo $__env->make('profile.partials.referral-block', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php echo $__env->make('profile.partials.telegram-account', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="lp-profile-block lp-profile-accordion" x-data="{ open: <?php echo \Illuminate\Support\Js::from($accordionOpenProfile)->toHtml() ?> }">
            <button
                type="button"
                class="lp-profile-accordion__trigger"
                @click="open = !open"
                :aria-expanded="open"
            >
                <span class="lp-profile-accordion__title">Редактирование профиля</span>
                <span class="lp-profile-accordion__chev" :class="{ 'lp-profile-accordion__chev--open': open }" aria-hidden="true">▾</span>
            </button>
            <div class="lp-profile-accordion__panel" x-show="open" x-cloak x-transition>
                <div class="max-w-xl">
                    <?php echo $__env->make('profile.partials.update-profile-information-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>

        <div class="lp-profile-block lp-profile-accordion" x-data="{ open: <?php echo \Illuminate\Support\Js::from($accordionOpenPassword)->toHtml() ?> }">
            <button
                type="button"
                class="lp-profile-accordion__trigger"
                @click="open = !open"
                :aria-expanded="open"
            >
                <span class="lp-profile-accordion__title">Смена пароля</span>
                <span class="lp-profile-accordion__chev" :class="{ 'lp-profile-accordion__chev--open': open }" aria-hidden="true">▾</span>
            </button>
            <div class="lp-profile-accordion__panel" x-show="open" x-cloak x-transition>
                <div class="max-w-xl">
                    <?php echo $__env->make('profile.partials.update-password-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>

        <div class="lp-profile-block lp-profile-accordion" x-data="{ open: <?php echo \Illuminate\Support\Js::from($accordionOpenDelete)->toHtml() ?> }">
            <button
                type="button"
                class="lp-profile-accordion__trigger"
                @click="open = !open"
                :aria-expanded="open"
            >
                <span class="lp-profile-accordion__title">Удаление аккаунта</span>
                <span class="lp-profile-accordion__chev" :class="{ 'lp-profile-accordion__chev--open': open }" aria-hidden="true">▾</span>
            </button>
            <div class="lp-profile-accordion__panel" x-show="open" x-cloak x-transition>
                <div class="max-w-xl">
                    <?php echo $__env->make('profile.partials.delete-user-form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>
            </div>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf89371928a89b76038cd3904d0f3e1f4)): ?>
<?php $attributes = $__attributesOriginalf89371928a89b76038cd3904d0f3e1f4; ?>
<?php unset($__attributesOriginalf89371928a89b76038cd3904d0f3e1f4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf89371928a89b76038cd3904d0f3e1f4)): ?>
<?php $component = $__componentOriginalf89371928a89b76038cd3904d0f3e1f4; ?>
<?php unset($__componentOriginalf89371928a89b76038cd3904d0f3e1f4); ?>
<?php endif; ?>
<?php /**PATH C:\Users\kfcku\Desktop\nadezhda.space\mainServer\resources\views/profile/edit.blade.php ENDPATH**/ ?>
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
    <?php echo $__env->make('partials.account-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
        /** @var \App\Models\User $me */
        $me = Auth::user();
        /** @var array<string, mixed> $account */
        $state = $account['state'];
        $sub = $account['subscription'];
        $live = in_array($state, ['active', 'trial'], true);
        $canIssueTrial = ! $live && $me->canSelfIssueCabinetTrial();
        $trialHours = (int) config('trial_subscription.hours', 8);
    ?>

    <div class="max-w-4xl mx-auto acc">
        <?php if(session('status') === 'test-key-issued'): ?>
            <div class="acc-note" style="margin-bottom:1rem;">Пробный доступ выдан. Скопируйте ссылку ниже и добавьте её в приложение.</div>
        <?php endif; ?>
        <?php $__errorArgs = ['test_key'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <div class="acc-note acc-note--warn" style="margin-bottom:1rem;"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

        <div class="acc-block">
            <div class="acc-status">
                <?php if($state === 'active'): ?>
                    <span class="acc-pill acc-pill--on">Активна</span>
                <?php elseif($state === 'trial'): ?>
                    <span class="acc-pill acc-pill--trial">Пробный период</span>
                <?php elseif($state === 'inactive'): ?>
                    <span class="acc-pill acc-pill--off">Не активна</span>
                <?php else: ?>
                    <span class="acc-pill acc-pill--off">Не оформлена</span>
                <?php endif; ?>

                <?php if($live && $account['expires_at']): ?>
                    <span class="acc-status__until">до <?php echo e($account['expires_at']->format('d.m.Y H:i')); ?></span>
                    <span class="acc-status__left">осталось <?php echo e($account['days_left']); ?> дн.</span>
                <?php elseif($live): ?>
                    <span class="acc-status__until">без срока</span>
                <?php elseif($state === 'inactive' && $account['expires_at']): ?>
                    <span class="acc-status__left">закончилась <?php echo e($account['expires_at']->format('d.m.Y')); ?></span>
                <?php endif; ?>
            </div>

            <?php if($sub): ?>
                <div class="acc-facts">
                    <div class="acc-fact">
                        <div class="acc-fact__k">Устройства</div>
                        <div class="acc-fact__v">
                            <?php if($account['unlimited_devices']): ?>
                                без лимита
                            <?php else: ?>
                                <?php echo e($account['used']); ?> из <?php echo e($account['device_limit']); ?>

                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="acc-fact">
                        <div class="acc-fact__k">Трафик</div>
                        <div class="acc-fact__v">безлимит</div>
                    </div>
                    <div class="acc-fact">
                        <div class="acc-fact__k">Номер</div>
                        <div class="acc-fact__v">#<?php echo e($sub->public_code); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($state === 'inactive'): ?>
                <p class="acc-hint" style="margin-top:1rem;">Ссылка и устройства сохранены. После оплаты всё заработает по той же ссылке.</p>
            <?php elseif($state === 'none'): ?>
                <p class="acc-hint" style="margin-top:1rem;">Оформите подписку — ссылка для приложения появится здесь и больше не будет меняться.</p>
            <?php endif; ?>

            <div class="acc-actions">
                <a href="<?php echo e(route('cabinet.payment')); ?>" class="acc-btn acc-btn--primary">
                    <?php echo e($state === 'active' ? 'Продлить' : 'Оплатить'); ?>

                </a>
                <?php if($sub): ?>
                    <a href="<?php echo e(route('cabinet.settings')); ?>" class="acc-btn">Устройства</a>
                <?php endif; ?>
                <?php if($canIssueTrial && $me->hasVerifiedIdentity()): ?>
                    <form method="POST" action="<?php echo e(route('cabinet.test_keys.store')); ?>">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="acc-btn">Пробный доступ на <?php echo e($trialHours); ?> ч</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if($canIssueTrial && ! $me->hasVerifiedIdentity()): ?>
                <p class="acc-hint" style="margin-top:0.75rem;">
                    Чтобы получить пробный доступ, подтвердите почту в <a href="<?php echo e(route('cabinet.profile')); ?>" class="lp-auth-secondary">профиле</a>.
                </p>
            <?php endif; ?>
        </div>

        <?php if($sub && $live): ?>
            <div class="acc-block">
                <div class="acc-chips"><span class="acc-chip">Как подключиться</span></div>
                <?php if (isset($component)) { $__componentOriginald324a7df2e6bf16434faf5efc8a8f370 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald324a7df2e6bf16434faf5efc8a8f370 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.cabinet-connect-howto','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('cabinet-connect-howto'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                    <div class="lp-copy-row" x-data="{ copied: false }">
                        <button
                            type="button"
                            class="lp-btn lp-btn--copy"
                            :class="copied ? 'lp-btn--copied' : ''"
                            x-on:click="
                                (async () => {
                                    try { await navigator.clipboard.writeText(<?php echo \Illuminate\Support\Js::from($sub->shareableSubUrl())->toHtml() ?>); copied = true; setTimeout(() => copied = false, 1600); }
                                    catch (e) { copied = false; }
                                })()
                            "
                        >
                            <span x-show="!copied">Скопировать ссылку</span>
                            <span x-show="copied" x-cloak>Скопировано</span>
                        </button>
                        <span class="lp-copy-hint">Ссылка подписки попадёт в буфер обмена.</span>
                        <div class="lp-subscription-url-fallback">
                            <span class="lp-subscription-url-fallback__label">Ссылка текстом</span>
                            <code class="lp-subscription-url-fallback__url"><?php echo e($sub->shareableSubUrl()); ?></code>
                        </div>
                    </div>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald324a7df2e6bf16434faf5efc8a8f370)): ?>
<?php $attributes = $__attributesOriginald324a7df2e6bf16434faf5efc8a8f370; ?>
<?php unset($__attributesOriginald324a7df2e6bf16434faf5efc8a8f370); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald324a7df2e6bf16434faf5efc8a8f370)): ?>
<?php $component = $__componentOriginald324a7df2e6bf16434faf5efc8a8f370; ?>
<?php unset($__componentOriginald324a7df2e6bf16434faf5efc8a8f370); ?>
<?php endif; ?>
            </div>
        <?php endif; ?>
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
<?php /**PATH C:\Users\kfcku\Desktop\nadezhda.space\mainServer\resources\views/cabinet/index.blade.php ENDPATH**/ ?>
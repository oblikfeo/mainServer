
<?php
    $terms = array_values(\App\Services\Account\AccountPricing::terms());
    $baseDevices = \App\Services\Account\AccountPricing::baseDevices();
    $maxDevices = \App\Services\Account\AccountPricing::maxDevices();
    $deviceRub = \App\Services\Account\AccountPricing::deviceMonthRub();
    $lastIndex = count($terms) - 1;
?>
<div class="pricing-column">
    <h3 class="pricing-column-title">Устройства</h3>
    <p class="pricing-column-hint"><?php echo e($baseDevices); ?> входят в подписку</p>
    <div class="pricing-cards">
        <div
            class="pricing-card lp-buy-devices"
            id="lp-buy-devices"
            data-base="<?php echo e($baseDevices); ?>"
            data-max="<?php echo e($maxDevices); ?>"
            data-device-rub="<?php echo e($deviceRub); ?>"
        >
            <div class="pricing-duration">Сколько устройств</div>
            <div class="lp-buy-devices__stepper">
                <button type="button" class="lp-buy-devices__btn" id="lp-buy-devices-minus" aria-label="Меньше устройств">&minus;</button>
                <span class="lp-buy-devices__count" id="lp-buy-devices-count" aria-live="polite"><?php echo e($baseDevices); ?></span>
                <button type="button" class="lp-buy-devices__btn" id="lp-buy-devices-plus" aria-label="Больше устройств">+</button>
            </div>
            <div class="pricing-per-month">+<?php echo e($deviceRub); ?>&nbsp;₽ в месяц за каждое дополнительное</div>
            <div class="pricing-note">До <?php echo e($maxDevices); ?> устройств на одном аккаунте. Добавить устройство можно и позже — в личном кабинете.</div>
        </div>
    </div>
</div>
<div class="pricing-column">
    <h3 class="pricing-column-title">Срок</h3>
    <p class="pricing-column-hint" id="lp-buy-devices-hint"><?php echo e($baseDevices); ?> устройства</p>
    <div class="pricing-cards">
        <?php $__currentLoopData = $terms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $term): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="pricing-card <?php echo e($i === $lastIndex ? 'pricing-card-popular' : ''); ?>">
                <div class="pricing-duration"><?php echo e($term['label']); ?></div>
                <div class="pricing-price"><span data-buy-price><?php echo e($term['amount_rub']); ?></span>&nbsp;₽</div>
                <?php if($term['discount_percent'] > 0): ?>
                    <div class="pricing-savings">Скидка <?php echo e($term['discount_percent']); ?>% на подписку</div>
                <?php endif; ?>
                <button
                    type="button"
                    class="lp-buy-pay-btn"
                    data-term="<?php echo e($term['key']); ?>"
                    data-term-months="<?php echo e($term['months']); ?>"
                    data-term-amount="<?php echo e($term['amount_rub']); ?>"
                >
                    Оплатить
                </button>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php /**PATH C:\Users\kfcku\Desktop\nadezhda.space\mainServer\resources\views/quick-buy/partials/pricing-buy-plaques.blade.php ENDPATH**/ ?>
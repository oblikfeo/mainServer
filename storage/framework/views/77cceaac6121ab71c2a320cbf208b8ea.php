
<?php $__currentLoopData = config('marketing.tariffs', []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plan): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        $rows = $plan['rows'] ?? [];
        $rowCount = count($rows);
    ?>
    <div class="pricing-column">
        <h3 class="pricing-column-title"><?php echo e($plan['title']); ?></h3>
        <?php if(filled($plan['pricing_hint'] ?? null)): ?>
            <p class="pricing-column-hint"><?php echo e($plan['pricing_hint']); ?></p>
        <?php endif; ?>
        <div class="pricing-cards">
            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $isLast = $i === $rowCount - 1;
                    $sub = $row['sub'] ?? null;
                    $plainSub = trim(strip_tags((string) $sub));
                    $classes = ['pricing-card'];
                    if (! empty($row['badge'] ?? null)) {
                        $classes[] = 'pricing-card-best';
                    } elseif (($plan['kind'] ?? '') === 'solo' && $isLast) {
                        $classes[] = 'pricing-card-popular';
                    }
                    $showSavings = filled($sub) && str_contains($plainSub, 'Выгода');
                    $showPerMonth = filled($sub) && ! $showSavings && (
                        str_contains($plainSub, '/мес') || str_contains($plainSub, '₽/мес')
                    );
                    $showRemainderSub = filled($sub) && ! $showSavings && ! $showPerMonth;
                ?>
                <div class="<?php echo e(implode(' ', $classes)); ?>">
                    <?php if(filled($row['badge'] ?? null)): ?>
                        <span class="pricing-tag"><?php echo e($row['badge']); ?></span>
                    <?php endif; ?>
                    <div class="pricing-duration"><?php echo e($row['period']); ?></div>
                    <div class="pricing-price"><?php echo e($row['amount']); ?>&nbsp;₽</div>
                    <?php if($showSavings): ?>
                        <div class="pricing-savings"><?php echo $sub; ?></div>
                    <?php elseif($showPerMonth): ?>
                        <div class="pricing-per-month"><?php echo $sub; ?></div>
                    <?php elseif($showRemainderSub): ?>
                        <div class="pricing-note"><?php echo $sub; ?></div>
                    <?php endif; ?>
                    <?php if(filled($row['note'] ?? null)): ?>
                        <div class="pricing-note"><?php echo $row['note']; ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH C:\Users\kfcku\Desktop\nadezhda.space\mainServer\resources\views2/partials/pricing-mock-plaques.blade.php ENDPATH**/ ?>
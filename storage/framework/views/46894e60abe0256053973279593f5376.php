<?php if(! empty($partnerLogo)): ?>
    <div class="lp-partner-logo">
        <img
            src="<?php echo e(asset($partnerLogo)); ?>"
            alt="<?php echo e($partnerLabel); ?>"
            class="lp-partner-logo__img"
            loading="eager"
            decoding="async"
        >
    </div>
<?php else: ?>
    <span class="lp-partner-invite__name" role="note" aria-label="<?php echo e($partnerLabel); ?>"><?php echo e(mb_strtoupper($partnerLabel, 'UTF-8')); ?></span>
<?php endif; ?>
<?php /**PATH C:\Users\kfcku\Desktop\nadezhda.space\mainServer\resources\views/auth/partials/partner-invite-card.blade.php ENDPATH**/ ?>
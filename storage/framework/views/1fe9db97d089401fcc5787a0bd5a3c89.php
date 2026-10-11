<div class="lp-payment-info">
    <?php $__currentLoopData = config('marketing.payment_notes', []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <span>✓ <?php echo $line; ?></span>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php /**PATH C:\Users\kfcku\Desktop\nadezhda.space\mainServer\resources\views2/partials/pricing-payment-notes.blade.php ENDPATH**/ ?>
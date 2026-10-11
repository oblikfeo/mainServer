<?php
    $telegramStartUrl = session('telegram_start_url');
?>

<div class="lp-profile-block" id="profile-telegram">
    <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 mb-0">Telegram</h2>

    <?php if(session('status') === 'telegram-linked'): ?>
        <p class="mt-3 text-sm font-semibold text-emerald-900 border-2 border-black bg-emerald-50 px-3 py-2">
            Telegram привязан к аккаунту.
        </p>
    <?php endif; ?>
    <?php if(session('status') === 'telegram-unlinked'): ?>
        <p class="mt-3 text-sm font-semibold text-slate-900 border-2 border-black bg-slate-50 px-3 py-2">
            Привязка Telegram отключена.
        </p>
    <?php endif; ?>

    <?php $__errorArgs = ['telegram_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <div class="mt-3 text-sm font-semibold text-red-700 border-2 border-black bg-red-50 px-3 py-2"><?php echo e($message); ?></div>
    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

    <?php if($user->telegram_id): ?>
        <dl class="lp-dl-grid lp-dl-grid--account mt-3">
            <div>
                <dt>Статус</dt>
                <dd>
                    <span class="lp-badge-pill lp-badge-pill--ok">Привязан</span>
                </dd>
            </div>
            <div>
                <dt>Telegram</dt>
                <dd class="font-mono break-all">
                    <?php if($user->telegram_username): ?>
                        <?php echo e('@'.$user->telegram_username); ?>

                    <?php else: ?>
                        id <?php echo e($user->telegram_id); ?>

                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt class="sr-only">Отвязать</dt>
                <dd class="lp-dl-grid__action">
                    <form method="POST" action="<?php echo e(route('cabinet.telegram.unlink')); ?>" class="inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="lp-account-verify-btn lp-secondary-outline">
                            Отвязать Telegram
                        </button>
                    </form>
                </dd>
            </div>
        </dl>
    <?php else: ?>
        <p class="mt-1 text-xs text-slate-600 leading-snug max-w-xl">
            Нажмите кнопку ниже на сайте, затем перейдите по открывшейся ссылке в Telegram
            (<code class="text-[11px]">t.me/… ?start=…</code>). Один переход завершает привязку; если открыть бота просто через поиск без ссылки из кабинета, привязка не выполнится.
        </p>
        <div class="mt-4">
            <?php if (! ($telegramStartUrl)): ?>
                <form method="POST" action="<?php echo e(route('cabinet.telegram.start')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="lp-account-verify-btn">
                        Привязать Telegram
                    </button>
                </form>
            <?php else: ?>
                <a
                    href="<?php echo e($telegramStartUrl); ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="lp-account-verify-btn inline-flex items-center justify-center text-center no-underline"
                >
                    Привязать Telegram
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\kfcku\Desktop\nadezhda.space\mainServer\resources\views/profile/partials/telegram-account.blade.php ENDPATH**/ ?>
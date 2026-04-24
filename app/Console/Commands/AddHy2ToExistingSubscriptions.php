<?php

namespace App\Console\Commands;

use App\Models\IssuedKey;
use App\Models\Subscription;
use App\Services\Hy2\BlitzClient;
use App\Services\Hy2\BlitzException;
use Illuminate\Console\Command;

class AddHy2ToExistingSubscriptions extends Command
{
    protected $signature = 'subscription:add-hy2
        {--dry-run : Показать что будет сделано, но не выполнять}';

    protected $description = 'Добавить Hysteria2-юзера (через Blitz) ко всем подпискам, у которых ещё нет hy2_username';

    public function handle(): int
    {
        if (! config('hy2.enabled')) {
            $this->error('HY2_ENABLED=false — включите Hysteria2 в .env');

            return self::FAILURE;
        }

        $subs = Subscription::query()
            ->whereNull('hy2_username')
            ->orderBy('id')
            ->get();

        if ($subs->isEmpty()) {
            $this->info('Все подписки уже имеют hy2-юзера — нечего делать.');

            return self::SUCCESS;
        }

        $this->info("Найдено подписок без hy2: {$subs->count()}");

        $dryRun = $this->option('dry-run');
        $blitz = new BlitzClient();
        $ok = 0;
        $fail = 0;

        foreach ($subs as $sub) {
            $username = 'hy2_'.bin2hex(random_bytes(5));
            $password = bin2hex(random_bytes(16));
            $quotaGb = (int) $sub->quota_gb;
            $days = $this->remainingDays($sub);

            if ($dryRun) {
                $this->line("  [dry-run] #{$sub->id} → {$username}, {$quotaGb} GB, {$days} дн.");
                $ok++;

                continue;
            }

            try {
                $blitz->addUser($username, $password, $quotaGb, $days);

                $sub->update([
                    'hy2_username' => $username,
                    'hy2_password' => $password,
                ]);

                if (! IssuedKey::query()->where('subscription_id', $sub->id)->where('bundle_id', 'hy2')->exists()) {
                    IssuedKey::query()->create(['bundle_id' => 'hy2', 'subscription_id' => $sub->id]);
                }

                $ok++;
                $this->line("  ✓ #{$sub->id} → {$username}");
            } catch (BlitzException $e) {
                $fail++;
                $this->warn("  ✗ #{$sub->id}: {$e->getMessage()}");
            }

            usleep(300_000);
        }

        $this->newLine();
        $this->info("Готово: {$ok} добавлено, {$fail} ошибок.");

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function remainingDays(Subscription $sub): int
    {
        $expiryMs = (int) $sub->expiry_ms;
        if ($expiryMs <= 0) {
            return 365;
        }

        $remaining = (int) ceil(($expiryMs / 1000 - time()) / 86400);

        return max($remaining, 1);
    }
}

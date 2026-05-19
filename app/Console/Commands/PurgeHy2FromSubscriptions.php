<?php

namespace App\Console\Commands;

use App\Models\IssuedKey;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Удаляет HY2 из БД: hy2_username/password и issued_keys bundle hy2.
 * Blitz на Hostkey к этому моменту должен быть снят или недоступен.
 */
final class PurgeHy2FromSubscriptions extends Command
{
    protected $signature = 'subscription:purge-hy2 {--dry-run : Только показать счётчики}';

    protected $description = 'Очистить HY2/Hysteria из всех подписок (поля БД и issued_keys)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $withHy2 = Subscription::query()
            ->where(function ($q) {
                $q->whereNotNull('hy2_username')->where('hy2_username', '!=', '')
                    ->orWhereNotNull('hy2_password')->where('hy2_password', '!=', '');
            })
            ->count();

        $hy2Keys = IssuedKey::query()->where('bundle_id', 'hy2')->count();

        $this->info("Подписок с hy2_*: {$withHy2}");
        $this->info("issued_keys (hy2): {$hy2Keys}");

        if ($dryRun) {
            $this->comment('Dry-run: изменений нет.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($withHy2): void {
            Subscription::query()
                ->where(function ($q) {
                    $q->whereNotNull('hy2_username')->where('hy2_username', '!=', '')
                        ->orWhereNotNull('hy2_password')->where('hy2_password', '!=', '');
                })
                ->update([
                    'hy2_username' => null,
                    'hy2_password' => null,
                ]);

            IssuedKey::query()->where('bundle_id', 'hy2')->delete();
        });

        $this->info("Очищено подписок: {$withHy2}, удалено issued_keys: {$hy2Keys}");

        return self::SUCCESS;
    }
}

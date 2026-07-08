<?php

namespace App\Console\Commands;

use App\Services\Trial\TrialFollowupEmailService;
use Illuminate\Console\Command;

class SendTrialFollowupEmailsCommand extends Command
{
    protected $signature = 'trial-followup:send {--dry-run : Только показать получателей, без отправки}';

    protected $description = 'Отправить follow-up письмо пользователям через 24 ч после окончания триала (один раз на аккаунт).';

    public function handle(TrialFollowupEmailService $service): int
    {
        $eligible = $service->findEligibleUsers();
        $dryRun = (bool) $this->option('dry-run');

        if ($eligible === []) {
            $this->info('Нет получателей.');

            return self::SUCCESS;
        }

        $this->info('Кандидатов: '.count($eligible));

        $sent = 0;
        $failed = 0;

        foreach ($eligible as $user) {
            $endedAt = $user->latestSubscriptionTrialEndedAt()?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?? '—';

            if ($dryRun) {
                $this->line("  [dry-run] #{$user->id} {$user->email} · триал закончился {$endedAt}");

                continue;
            }

            if ($service->sendForUser($user)) {
                $sent++;
                $this->line("  отправлено: #{$user->id} {$user->email}");
            } else {
                $failed++;
                $this->warn("  пропуск: #{$user->id} {$user->email}");
            }
        }

        if (! $dryRun) {
            $this->info("Готово. Отправлено: {$sent}, пропущено: {$failed}.");
        }

        return self::SUCCESS;
    }
}

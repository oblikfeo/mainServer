<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;

final class PromoStatsCommand extends Command
{
    protected $signature = 'promo:stats {--list : Показать пользователей}';

    protected $description = 'Сколько регистраций с промокодом';

    public function handle(): int
    {
        $users = User::query()
            ->whereNotNull('registration_promo_code')
            ->where('registration_promo_code', '!=', '');

        $total = (clone $users)->count();
        $this->line('total='.$total);

        $byCode = (clone $users)
            ->selectRaw('registration_promo_code as code, count(*) as cnt')
            ->groupBy('registration_promo_code')
            ->orderByDesc('cnt')
            ->get();

        foreach ($byCode as $row) {
            $this->line(((string) $row->code)."\t".((int) $row->cnt));
        }

        $trialSubs = Subscription::query()
            ->whereNotNull('promo_code')
            ->where('promo_code', '!=', '')
            ->count();
        $this->line('promo_trials='.$trialSubs);

        if (! $this->option('list')) {
            return self::SUCCESS;
        }

        $this->newLine();
        foreach ((clone $users)->orderByDesc('id')->get(['id', 'email', 'registration_promo_code', 'created_at']) as $user) {
            $this->line(sprintf(
                "%d\t%s\t%s\t%s",
                (int) $user->id,
                (string) $user->email,
                (string) $user->registration_promo_code,
                $user->created_at?->timezone((string) config('app.timezone'))->format('Y-m-d H:i') ?? '-',
            ));
        }

        return self::SUCCESS;
    }
}

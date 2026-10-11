<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Services\Account\AccountPricing;
use App\Services\Account\DeviceSlots;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Разовый перенос данных на схему «аккаунт = подписка».
 * По умолчанию — сухой прогон (ничего не меняет). С --apply делает всё в одной транзакции
 * и пишет отчёт «было → стало» в storage/app.
 */
class AccountMigrateCommand extends Command
{
    protected $signature = 'account:migrate {--apply : Выполнить перенос (без флага — только показать план)}';

    protected $description = 'Перенос на «аккаунт = подписка»: одна подписка на аккаунт, семейные → 2 базовых + доп. слоты';

    /** Тариф «Семейная»: ровно столько устройств превращаются в базовые + доп. слоты. */
    private const FAMILY_DEVICES = 5;

    /**
     * Аккаунты с двумя активными подписками: решения приняты владельцем сервиса вручную.
     * Если фактическое состояние не совпадает с ожидаемым (обе активны) — перенос останавливается.
     *
     * @var array<string, array{type: string, keep: int, other: int}>
     */
    private const SPECIAL = [
        // Оставить старую, новую в архив; её устройства → 2 доп. слота с остатком её срока.
        'saveljev.ol3xiy@yandex.ru' => ['type' => 'merge_devices', 'keep' => 336, 'other' => 476],
        // Вторая подписка не использовалась: её ОСТАТОК срока прибавить к первой, вторую в архив.
        'glokssia123@mail.ru' => ['type' => 'merge_time', 'keep' => 335, 'other' => 441],
        // Семейную отвязать от аккаунта (доработает по своей ссылке без входа в кабинет), остаётся обычная.
        'nickpochtin@rambler.ru' => ['type' => 'detach', 'keep' => 319, 'other' => 257],
    ];

    /** @var array<int, array<string, mixed>> */
    private array $report = [];

    /** @var array<string, int> */
    private array $stats = [];

    private int $nowMs = 0;

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->nowMs = DeviceSlots::nowMs();

        $this->line($apply ? '=== ПЕРЕНОС (изменения будут записаны) ===' : '=== СУХОЙ ПРОГОН (ничего не меняется) ===');

        try {
            DB::beginTransaction();
            $this->run_();
            $this->verifyNoDuplicates();

            if ($apply) {
                DB::commit();
            } else {
                DB::rollBack();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('ОСТАНОВЛЕНО, ничего не изменено: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('--- Итог ---');
        foreach ($this->stats as $k => $v) {
            $this->line(str_pad($k, 46).' '.$v);
        }

        if ($apply) {
            $this->ensureUniqueIndex();
            $path = 'account-migration-'.now()->format('Ymd-His').'.json';
            file_put_contents(storage_path('app/'.$path), json_encode($this->report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->info('Готово. Отчёт: storage/app/'.$path);
        } else {
            $this->comment('Это был сухой прогон. Для выполнения: php artisan account:migrate --apply');
        }

        return self::SUCCESS;
    }

    private function run_(): void
    {
        $users = User::query()->orderBy('id')->get(['id', 'email']);
        $byUser = Subscription::query()->whereNotNull('user_id')->orderBy('id')->get()->groupBy('user_id');

        $this->bump('аккаунтов всего', $users->count());
        $this->bump('подписок с владельцем', $byUser->flatten()->count());

        foreach ($users as $user) {
            /** @var Collection<int, Subscription> $subs */
            $subs = $byUser->get($user->id, collect());
            if ($subs->isEmpty()) {
                $this->bump('аккаунтов без подписки');

                continue;
            }

            $special = self::SPECIAL[strtolower((string) $user->email)] ?? null;
            $keep = $special !== null
                ? $this->applySpecial($user, $subs, $special)
                : $this->pickKeep($user, $subs);

            foreach ($subs as $sub) {
                if ($sub->id !== $keep->id && $sub->user_id !== null) {
                    $this->archive($user, $sub, 'лишняя подписка аккаунта');
                }
            }

            $this->normalizeKept($user, $keep);
        }
    }

    /** @param Collection<int, Subscription> $subs */
    private function pickKeep(User $user, Collection $subs): Subscription
    {
        $active = $subs->filter(fn (Subscription $s) => $this->active($s))->values();
        if ($active->count() > 1) {
            throw new RuntimeException(
                'у аккаунта '.$user->email.' (id '.$user->id.') несколько активных подписок: #'
                .$active->pluck('id')->implode(', #').' — нужно решение вручную'
            );
        }
        if ($active->count() === 1) {
            if ($subs->count() > 1) {
                $this->bump('аккаунтов: активная + истёкшие');
            } else {
                $this->bump('аккаунтов: одна активная');
            }

            return $active->first();
        }

        $this->bump($subs->count() > 1 ? 'аккаунтов: несколько, все истёкшие' : 'аккаунтов: одна истёкшая');

        // Самая свежая по сроку, при равенстве — по номеру.
        return $subs->sortBy([['expiry_ms', 'desc'], ['id', 'desc']])->first();
    }

    /**
     * @param  Collection<int, Subscription>  $subs
     * @param  array{type: string, keep: int, other: int}  $rule
     */
    private function applySpecial(User $user, Collection $subs, array $rule): Subscription
    {
        $keep = $subs->firstWhere('id', $rule['keep']);
        $other = $subs->firstWhere('id', $rule['other']);
        if ($keep === null || $other === null) {
            throw new RuntimeException('особый аккаунт '.$user->email.': не найдены подписки #'.$rule['keep'].' / #'.$rule['other']);
        }
        if (! $this->active($keep) || ! $this->active($other)) {
            throw new RuntimeException(
                'особый аккаунт '.$user->email.': ожидались две активные подписки (#'.$keep->id.' и #'.$other->id
                .'), но одна уже истекла — правило нужно пересмотреть вручную'
            );
        }
        $otherLeftMs = max(0, (int) $other->expiry_ms - $this->nowMs);
        $this->bump('особых аккаунтов');

        if ($rule['type'] === 'merge_devices') {
            $before = $this->snapshot($keep);
            [$hashes, $meta] = $this->mergeBindings($keep, $other);
            $keep->bound_hwid_hashes = $hashes === [] ? null : $hashes;
            $keep->bound_hwid_meta = $meta === [] ? null : $meta;
            $keep->save();
            $slots = max(0, (int) $other->devices);
            DeviceSlots::addMs($keep, $slots, $otherLeftMs, null, $this->nowMs);
            $this->log($user, $keep, 'слияние: +'.$slots.' доп. слота с остатком '.$this->days($otherLeftMs).' дн., устройства #'.$other->id.' перенесены', $before);
            $this->archive($user, $other, 'слита в #'.$keep->id, expireNow: true);
        } elseif ($rule['type'] === 'merge_time') {
            $before = $this->snapshot($keep);
            $keep->expiry_ms = (int) $keep->expiry_ms + $otherLeftMs;
            $keep->save();
            $this->log($user, $keep, 'срок +'.$this->days($otherLeftMs).' дн. (остаток #'.$other->id.')', $before);
            $this->archive($user, $other, 'остаток срока перенесён в #'.$keep->id, expireNow: true);
        } elseif ($rule['type'] === 'detach') {
            $this->archive($user, $other, 'отвязана от аккаунта, работает по своей ссылке до конца срока');
        } else {
            throw new RuntimeException('неизвестное правило «'.$rule['type'].'» для '.$user->email);
        }

        return $keep;
    }

    /** Подписка, которая остаётся у аккаунта: чистим привязки у неактивной, семейную переводим на слоты. */
    private function normalizeKept(User $user, Subscription $keep): void
    {
        $keep->refresh();

        if (! $this->active($keep)) {
            $before = $this->snapshot($keep);
            $changed = false;
            if (! empty($keep->bound_hwid_hashes) || ! empty($keep->bound_hwid_meta)) {
                $keep->bound_hwid_hashes = null;
                $keep->bound_hwid_meta = null;
                $changed = true;
            }
            $base = AccountPricing::baseDevices();
            if (! $keep->is_trial && (int) $keep->devices !== $base) {
                $keep->devices = $base;
                $changed = true;
            }
            if ($changed) {
                $keep->save();
                $this->bump('неактивных: привязки очищены / база '.$base.' устр.');
                $this->log($user, $keep, 'неактивна: привязки очищены, базовые устройства = '.$base, $before);
            }

            return;
        }

        if ((int) $keep->devices === self::FAMILY_DEVICES && (int) $keep->expiry_ms > 0) {
            $before = $this->snapshot($keep);
            $base = AccountPricing::baseDevices();
            $extra = self::FAMILY_DEVICES - $base;
            $leftMs = max(0, (int) $keep->expiry_ms - $this->nowMs);
            $keep->devices = $base;
            $keep->save();
            DeviceSlots::addMs($keep, $extra, $leftMs, null, $this->nowMs);
            $this->bump('семейных → '.$base.' базовых + '.$extra.' доп.');
            $this->log($user, $keep, 'семейная: '.$base.' базовых + '.$extra.' доп. слота до конца срока ('.$this->days($leftMs).' дн.)', $before);
        } elseif (! in_array((int) $keep->devices, [AccountPricing::baseDevices(), 1], true)) {
            $this->bump('активных с особым числом базовых устройств');
            $this->line('  особый лимит оставлен: '.$user->email.' #'.$keep->id.' — '.$keep->devices.' устр.');
        }
    }

    private function archive(User $user, Subscription $sub, string $reason, bool $expireNow = false): void
    {
        $before = $this->snapshot($sub);
        $sub->archived_from_user_id = $sub->user_id;
        $sub->archived_at = now();
        $sub->user_id = null;
        if ($expireNow && $this->active($sub)) {
            $sub->expiry_ms = $this->nowMs;
        }
        $sub->save();
        $this->bump('подписок отвязано от аккаунта (архив)');
        $this->log($user, $sub, 'АРХИВ: '.$reason, $before);
    }

    /** @return array{0: array<int, string>, 1: array<string, mixed>} */
    private function mergeBindings(Subscription $keep, Subscription $other): array
    {
        $clean = static fn ($v) => array_values(array_filter(is_array($v) ? $v : [], static fn ($h) => is_string($h) && strlen($h) === 64));
        $hashes = array_values(array_unique(array_merge($clean($keep->bound_hwid_hashes), $clean($other->bound_hwid_hashes))));
        $meta = array_merge(
            is_array($other->bound_hwid_meta) ? $other->bound_hwid_meta : [],
            is_array($keep->bound_hwid_meta) ? $keep->bound_hwid_meta : [],
        );

        return [$hashes, array_intersect_key($meta, array_flip($hashes))];
    }

    private function verifyNoDuplicates(): void
    {
        $dups = DB::table('subscriptions')
            ->select('user_id', DB::raw('COUNT(*) as c'))
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        if ($dups->isNotEmpty()) {
            throw new RuntimeException('после переноса у аккаунтов остались лишние подписки: user_id '.$dups->pluck('user_id')->implode(', '));
        }
        $this->bump('аккаунтов с двумя подписками после переноса', 0);
    }

    private function ensureUniqueIndex(): void
    {
        if (Schema::hasIndex('subscriptions', 'subscriptions_user_id_unique')) {
            return;
        }
        Schema::table('subscriptions', function ($table) {
            $table->unique('user_id', 'subscriptions_user_id_unique');
        });
        $this->line('Уникальный индекс subscriptions.user_id создан.');
    }

    private function active(Subscription $s): bool
    {
        return (int) $s->expiry_ms <= 0 || (int) $s->expiry_ms > $this->nowMs;
    }

    private function days(int $ms): string
    {
        return (string) round($ms / DeviceSlots::DAY_MS, 1);
    }

    /** @return array<string, mixed> */
    private function snapshot(Subscription $s): array
    {
        return [
            'user_id' => $s->user_id,
            'expiry_ms' => (int) $s->expiry_ms,
            'devices' => (int) $s->devices,
            'bound' => is_array($s->bound_hwid_hashes) ? $s->bound_hwid_hashes : [],
            'bound_meta' => is_array($s->bound_hwid_meta) ? $s->bound_hwid_meta : [],
        ];
    }

    /** @param array<string, mixed> $before */
    private function log(User $user, Subscription $sub, string $what, array $before): void
    {
        $sub->refresh();
        $this->report[] = [
            'user_id' => $user->id,
            'email' => $user->email,
            'subscription_id' => $sub->id,
            'public_code' => $sub->public_code,
            'action' => $what,
            'before' => $before,
            'after' => $this->snapshot($sub) + ['slots' => $sub->deviceSlots()->count()],
        ];
        $this->line('  '.$user->email.' #'.$sub->id.' — '.$what);
    }

    private function bump(string $key, int $by = 1): void
    {
        $this->stats[$key] = ($this->stats[$key] ?? 0) + $by;
    }
}

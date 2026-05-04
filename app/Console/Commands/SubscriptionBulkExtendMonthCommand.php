<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Services\Hy2\BlitzClient;
use App\Services\Hy2\BlitzException;
use App\Services\Subscription\SubscriptionCalendarExtension;
use App\Services\Xui\XuiPanelClient;
use App\Services\Xui\XuiPanelException;
use Illuminate\Console\Command;
use Throwable;

/**
 * Массовое продление подписок: добавляет N календарных дней и сбрасывает счётчики
 * фактически потраченного трафика на 3x-ui (FI/NL) и в Hy2 (Blitz reset-user).
 *
 * Существующие vless:// и hy2:// ссылки остаются прежними — у клиентов после команды
 * «как новый месяц»: дата истечения = текущая+30, использовано 0 ГБ.
 */
class SubscriptionBulkExtendMonthCommand extends Command
{
    protected $signature = 'subscription:bulk-extend-month
        {emails* : Email пользователей (один аргумент = один email)}
        {--days=30 : Сколько календарных дней добавить к expiry_ms}
        {--dry-run : Только показать действия, ничего не менять}';

    protected $description = 'Продлить подписки указанных email на N дней и обнулить счётчики трафика на 3x-ui (FI/NL) и Hy2 (Blitz)';

    public function handle(SubscriptionCalendarExtension $extender): int
    {
        $emails = collect($this->argument('emails'))
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter(fn ($e) => $e !== '')
            ->unique()
            ->values()
            ->all();

        $days = (int) $this->option('days');
        $dry = (bool) $this->option('dry-run');

        if ($emails === []) {
            $this->error('Не передано ни одного email.');

            return self::FAILURE;
        }
        if ($days < 1) {
            $this->error('--days должен быть >= 1');

            return self::FAILURE;
        }

        $this->line(sprintf(
            '%s продление: %d email × +%d дней + reset traffic',
            $dry ? '[dry-run]' : 'Запуск',
            count($emails),
            $days
        ));

        $okEmails = [];
        $failEmails = [];

        foreach ($emails as $email) {
            $this->line('');
            $this->line("=== {$email} ===");

            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                $this->error('  пользователь не найден');
                $failEmails[] = $email;

                continue;
            }

            $subs = $user->subscriptions()->orderBy('id')->get();
            if ($subs->isEmpty()) {
                $this->error('  у пользователя нет подписок');
                $failEmails[] = $email;

                continue;
            }

            $allOk = true;
            foreach ($subs as $sub) {
                $allOk = $this->processSubscription($sub, $days, $dry, $extender) && $allOk;
            }

            if ($allOk) {
                $okEmails[] = $email;
            } else {
                $failEmails[] = $email;
            }
        }

        $this->line('');
        $this->info(sprintf('Готово: успехов %d, с ошибками %d', count($okEmails), count($failEmails)));
        if ($failEmails !== []) {
            $this->warn('С ошибками: '.implode(', ', $failEmails));
        }

        return $failEmails === [] ? self::SUCCESS : self::FAILURE;
    }

    private function processSubscription(
        Subscription $sub,
        int $days,
        bool $dry,
        SubscriptionCalendarExtension $extender,
    ): bool {
        $oldExpiry = (int) $sub->expiry_ms;
        $oldExpiryHuman = $oldExpiry > 0
            ? date('Y-m-d H:i', (int) ($oldExpiry / 1000))
            : '∞';

        $this->line(sprintf(
            '  sub #%d  expiry=%s  fi=%s  nl=%s  hy2=%s  quota=%dGB',
            $sub->id,
            $oldExpiryHuman,
            $sub->fi_sub_id ?: '-',
            $sub->nl_sub_id ?: '-',
            $sub->hy2_username ?: '-',
            (int) $sub->quota_gb,
        ));

        if ($dry) {
            $this->line('    [dry-run] +'.$days.' дн.; reset trafic FI/NL/Hy2');

            return true;
        }

        $ok = true;

        try {
            $extender->addCalendarDays($sub, $days);
            $sub->refresh();
            $newExpiryHuman = $sub->expiry_ms > 0
                ? date('Y-m-d H:i', (int) ($sub->expiry_ms / 1000))
                : '∞';
            $this->info("    expiry: {$oldExpiryHuman} → {$newExpiryHuman}");
        } catch (Throwable $e) {
            $this->error('    addCalendarDays: '.$e->getMessage());
            $ok = false;
        }

        $ok = $this->resetXuiTraffic($sub) && $ok;
        $ok = $this->resetHy2Traffic($sub) && $ok;

        return $ok;
    }

    private function resetXuiTraffic(Subscription $sub): bool
    {
        $bundleOrder = config('xui.bundle_order', ['fi', 'nl']);
        $allOk = true;

        foreach ($bundleOrder as $key) {
            $subIdField = $key.'_sub_id';
            $subId = (string) ($sub->$subIdField ?? '');
            if ($subId === '') {
                continue;
            }
            $node = config('xui.nodes.'.$key, []);
            if (! is_array($node)) {
                continue;
            }

            $base = (string) ($node['panel_base'] ?? '');
            $user = (string) ($node['panel_username'] ?? config('xui.panel_username', ''));
            $pass = (string) ($node['panel_password'] ?? config('xui.panel_password', ''));
            $inboundId = (int) ($node['inbound_id'] ?? 0);
            if ($base === '' || $inboundId < 1 || $user === '' || $pass === '') {
                $this->warn("    {$key}: пропуск (нет конфига)");

                continue;
            }

            try {
                $client = new XuiPanelClient($base);
                $client->login($user, $pass);
                $inbound = $client->getInboundById($inboundId);
                $email = $this->emailBySubId($inbound, $subId);
                if ($email === null) {
                    $this->warn("    {$key}: клиент с subId={$subId} не найден в панели");
                    $allOk = false;

                    continue;
                }
                $client->resetClientTraffic($inboundId, $email);
                $this->info("    {$key}: traffic reset для {$email}");
            } catch (XuiPanelException|Throwable $e) {
                $this->error("    {$key}: ".$e->getMessage());
                $allOk = false;
            }
        }

        return $allOk;
    }

    private function resetHy2Traffic(Subscription $sub): bool
    {
        $username = (string) ($sub->hy2_username ?? '');
        if ($username === '' || ! config('hy2.enabled')) {
            return true;
        }

        try {
            (new BlitzClient())->resetUser($username);
            $this->info("    hy2: traffic reset для {$username}");

            return true;
        } catch (BlitzException|Throwable $e) {
            $this->error('    hy2: '.$e->getMessage());

            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $inbound
     */
    private function emailBySubId(array $inbound, string $subId): ?string
    {
        $settings = json_decode((string) ($inbound['settings'] ?? ''), true);
        if (! is_array($settings)) {
            return null;
        }
        $clients = $settings['clients'] ?? [];
        if (! is_array($clients)) {
            return null;
        }
        foreach ($clients as $c) {
            if (! is_array($c)) {
                continue;
            }
            if ((string) ($c['subId'] ?? '') === $subId) {
                $email = (string) ($c['email'] ?? '');

                return $email !== '' ? $email : null;
            }
        }

        return null;
    }
}

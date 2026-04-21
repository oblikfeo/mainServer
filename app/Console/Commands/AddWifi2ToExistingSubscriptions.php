<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Xui\XuiPanelClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class AddWifi2ToExistingSubscriptions extends Command
{
    protected $signature = 'subscriptions:add-wifi2 {--dry-run : Только показать что будет сделано}';

    protected $description = 'Добавить WiFi2 клиентов для всех существующих подписок без wifi2_sub_id';

    private const BYTES_PER_GB = 1_073_741_824;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $node = config('xui.nodes.wifi2', []);
        $base = (string) ($node['panel_base'] ?? '');
        $user = (string) ($node['panel_username'] ?? config('xui.panel_username', ''));
        $pass = (string) ($node['panel_password'] ?? config('xui.panel_password', ''));
        $inboundId = (int) ($node['inbound_id'] ?? 0);
        $prefix = (string) ($node['client_email_prefix'] ?? 'wifi2');
        $flow = (string) ($node['client_flow'] ?? '');

        if ($base === '' || $inboundId < 1) {
            $this->error('WiFi2 нода не настроена (XUI_WIFI2_BASE, XUI_WIFI2_INBOUND_ID)');

            return 1;
        }
        if ($user === '' || $pass === '') {
            $this->error('Не заданы креды 3x-ui для WiFi2 (XUI_WIFI2_USER/PASSWORD или XUI_PANEL_USER/PASSWORD)');

            return 1;
        }

        $subscriptions = Subscription::query()
            ->whereNull('wifi2_sub_id')
            ->orWhere('wifi2_sub_id', '')
            ->get();

        $this->info("Найдено подписок без WiFi2: {$subscriptions->count()}");

        if ($subscriptions->isEmpty()) {
            $this->info('Нечего обновлять.');

            return 0;
        }

        if ($dryRun) {
            $this->warn('--dry-run: изменения не применяются');
            foreach ($subscriptions as $sub) {
                $this->line("  ID {$sub->id}, token: ...".substr((string) $sub->token, -8));
            }

            return 0;
        }

        $panel = new XuiPanelClient($base);
        $panel->login($user, $pass);

        $success = 0;
        $failed = 0;

        foreach ($subscriptions as $sub) {
            try {
                $subId = bin2hex(random_bytes(8));
                $email = $prefix.'-'.substr($subId, 0, 10);
                $uid = (string) Str::uuid();

                $nodeCount = count(config('xui.bundle_order', ['wifi', 'wifi2', 'fi', 'nl']));
                $quotaBytes = (int) $sub->quota_gb * self::BYTES_PER_GB;
                $bytesPerNode = max(1, intdiv($quotaBytes, $nodeCount));

                $clientDef = [
                    'id' => $uid,
                    'email' => $email,
                    'flow' => $flow,
                    'limitIp' => max(0, (int) $sub->devices),
                    'totalGB' => $bytesPerNode,
                    'expiryTime' => (int) $sub->expiry_ms,
                    'enable' => true,
                    'tgId' => 0,
                    'subId' => $subId,
                ];

                $panel->addInboundClient($inboundId, $clientDef);
                $sub->update(['wifi2_sub_id' => $subId]);

                $this->info("✓ ID {$sub->id}: wifi2_sub_id = {$subId}");
                $success++;
            } catch (Throwable $e) {
                $this->error("✗ ID {$sub->id}: ".$e->getMessage());
                $failed++;
            }
        }

        $panel->restartXray();

        $this->newLine();
        $this->info("Готово. Успешно: {$success}, ошибок: {$failed}");

        return $failed > 0 ? 1 : 0;
    }
}

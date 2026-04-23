<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\Xui\XuiPanelClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class AddWifiToExistingSubscriptions extends Command
{
    protected $signature = 'subscriptions:add-wifi {--dry-run : Только показать что будет сделано}';

    protected $description = 'Добавить WiFi клиентов для всех существующих подписок без wifi_sub_id';

    private const BYTES_PER_GB = 1_073_741_824;

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $user = (string) config('xui.panel_username');
        $pass = (string) config('xui.panel_password');
        $wifiNode = config('xui.nodes.wifi', []);

        if ($user === '' || $pass === '') {
            $this->error('Не заданы XUI_PANEL_USER / XUI_PANEL_PASSWORD');
            return 1;
        }

        $base = (string) ($wifiNode['panel_base'] ?? '');
        $inboundId = (int) ($wifiNode['inbound_id'] ?? 0);
        $prefix = (string) ($wifiNode['client_email_prefix'] ?? 'wifi');
        $flow = (string) ($wifiNode['client_flow'] ?? 'xtls-rprx-vision');

        if ($base === '' || $inboundId < 1) {
            $this->error('WiFi нода не настроена (XUI_WIFI_BASE, XUI_WIFI_INBOUND_ID)');
            return 1;
        }

        $subscriptions = Subscription::query()
            ->whereNull('wifi_sub_id')
            ->orWhere('wifi_sub_id', '')
            ->get();

        $this->info("Найдено подписок без WiFi: {$subscriptions->count()}");

        if ($subscriptions->isEmpty()) {
            $this->info('Нечего обновлять.');
            return 0;
        }

        if ($dryRun) {
            $this->warn('--dry-run: изменения не применяются');
            foreach ($subscriptions as $sub) {
                $this->line("  ID {$sub->id}, token: ...".substr($sub->token, -8));
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

                $quotaBytes = (int) $sub->quota_gb * self::BYTES_PER_GB;

                $clientDef = [
                    'id' => $uid,
                    'email' => $email,
                    'flow' => $flow,
                    'limitIp' => max(0, (int) $sub->devices),
                    'totalGB' => $quotaBytes,
                    'expiryTime' => (int) $sub->expiry_ms,
                    'enable' => true,
                    'tgId' => 0,
                    'subId' => $subId,
                ];

                $panel->addInboundClient($inboundId, $clientDef);

                $sub->update(['wifi_sub_id' => $subId]);

                $this->info("✓ ID {$sub->id}: wifi_sub_id = {$subId}");
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

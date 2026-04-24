<?php

namespace App\Services\Xui;

use App\Models\Subscription;
use App\Services\Hy2\BlitzClient;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Проброс полного лимита подписки в 3x-ui как totalGB (байты) на каждом клиенте.
 */
final class XuiSubscriptionQuotaSync
{
    public function syncForSubscription(Subscription $sub): void
    {
        $perNodeBytes = $sub->perNodeTotalBytes();

        $bundleOrder = config('xui.bundle_order', ['fi', 'nl']);

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
            $user = (string) ($node['panel_username'] ?? config('xui.panel_username', ''));
            $pass = (string) ($node['panel_password'] ?? config('xui.panel_password', ''));
            $base = (string) ($node['panel_base'] ?? '');
            $inboundId = (int) ($node['inbound_id'] ?? 0);
            if ($base === '' || $inboundId < 1 || $user === '' || $pass === '') {
                continue;
            }

            try {
                $client = new XuiPanelClient($base);
                $client->login($user, $pass);
                $inbound = $client->getInboundById($inboundId);
                $row = $this->clientRowBySubId($inbound, $subId);
                if ($row === null) {
                    continue;
                }
                $uuid = (string) ($row['id'] ?? '');
                if ($uuid === '') {
                    continue;
                }
                $row['totalGB'] = $perNodeBytes;
                $client->updateInboundClient($inboundId, $uuid, $row);
            } catch (Throwable $e) {
                Log::warning('xui.subscription.quota_sync_failed', [
                    'subscription_id' => $sub->id,
                    'node' => $key,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $hy2Username = (string) ($sub->hy2_username ?? '');
        if ($hy2Username !== '' && config('hy2.enabled')) {
            $quotaGb = (int) $sub->quota_gb;
            try {
                (new BlitzClient())->editUser($hy2Username, trafficGb: $quotaGb > 0 ? $quotaGb : 0);
            } catch (Throwable $e) {
                Log::warning('hy2.quota_sync_failed', [
                    'subscription_id' => $sub->id,
                    'username' => $hy2Username,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $inbound
     */
    private function clientRowBySubId(array $inbound, string $subId): ?array
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
                return $c;
            }
        }

        return null;
    }
}


<?php

namespace App\Services\Xui;

use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Проброс квоты подписки в 3x-ui как totalGB (байты) на каждом клиенте FI/NL.
 */
final class XuiSubscriptionQuotaSync
{
    public function syncForSubscription(Subscription $sub): void
    {
        $user = (string) config('xui.panel_username');
        $pass = (string) config('xui.panel_password');
        if ($user === '' || $pass === '') {
            return;
        }

        $perNodeBytes = $sub->perNodeTotalBytes();

        foreach (['fi', 'nl'] as $key) {
            $subId = $key === 'fi' ? (string) $sub->fi_sub_id : (string) $sub->nl_sub_id;
            if ($subId === '') {
                continue;
            }
            $node = config('xui.nodes.'.$key, []);
            if (! is_array($node)) {
                continue;
            }
            $base = (string) ($node['panel_base'] ?? '');
            $inboundId = (int) ($node['inbound_id'] ?? 0);
            if ($base === '' || $inboundId < 1) {
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


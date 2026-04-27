<?php

namespace App\Services\Xui;

use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Проброс срока подписки (expiryTime) в 3x-ui на обоих клиентах по subId.
 */
final class XuiSubscriptionExpirySync
{
    public function syncForSubscription(Subscription $sub): void
    {
        $expiryMs = (int) $sub->expiry_ms;
        if ($expiryMs < 0) {
            $expiryMs = 0;
        }

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
                $row['expiryTime'] = $expiryMs;
                $client->updateInboundClient($inboundId, $uuid, $row);
            } catch (Throwable $e) {
                Log::warning('xui.subscription.expiry_sync_failed', [
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

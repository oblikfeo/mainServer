<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use App\Services\Hy2\BlitzClient;
use App\Services\Xui\XuiPanelClient;
use App\Services\Xui\XuiPanelException;
use Illuminate\Support\Facades\Log;
use Throwable;

final class DestroySubscription
{
    /**
     * Удаляет клиентов в 3x-ui по всем узлам bundle_order, перезапускает Xray, затем запись подписки (issued_keys — cascade).
     *
     * @throws XuiPanelException
     */
    public function destroy(Subscription $subscription): void
    {
        $user = (string) config('xui.panel_username');
        $pass = (string) config('xui.panel_password');
        $nodes = config('xui.nodes', []);

        if ($user === '' || $pass === '') {
            throw new XuiPanelException('Не заданы XUI_PANEL_USER / XUI_PANEL_PASSWORD');
        }

        $bundleOrder = config('xui.bundle_order', ['fi', 'nl']);

        foreach ($bundleOrder as $bundleKey) {
            $subIdField = $bundleKey.'_sub_id';
            $subId = (string) ($subscription->$subIdField ?? '');
            if ($subId === '') {
                continue;
            }

            $node = $nodes[$bundleKey] ?? null;
            if (! is_array($node)) {
                continue;
            }

            $base = (string) ($node['panel_base'] ?? '');
            $inboundId = (int) ($node['inbound_id'] ?? 0);
            if ($base === '' || $inboundId < 1) {
                continue;
            }

            $email = $this->clientEmail($node, $subId);

            try {
                $client = new XuiPanelClient($base);
                $client->login($user, $pass);
                $client->deleteInboundClientByEmail($inboundId, $email);
                $client->restartXray();
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                throw new XuiPanelException(
                    "Узел «{$bundleKey}» ({$email}): {$msg}",
                    previous: $e
                );
            }
        }

        $hy2Username = (string) ($subscription->hy2_username ?? '');
        if ($hy2Username !== '' && config('hy2.enabled')) {
            try {
                (new BlitzClient())->removeUser($hy2Username);
            } catch (Throwable $e) {
                Log::warning('hy2.destroy_user_failed', [
                    'subscription_id' => $subscription->id,
                    'username' => $hy2Username,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $subscription->delete();
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function clientEmail(array $node, string $subId): string
    {
        $prefix = (string) ($node['client_email_prefix'] ?? '');

        return $prefix.'-'.substr($subId, 0, 10);
    }
}

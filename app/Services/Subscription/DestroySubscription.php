<?php

namespace App\Services\Subscription;

use App\Models\Subscription;
use App\Services\Xui\XuiPanelClient;
use App\Services\Xui\XuiPanelException;
use Throwable;

final class DestroySubscription
{
    /**
     * Удаляет клиентов в 3x-ui (FI/NL) по email, перезапускает Xray, затем запись подписки (issued_keys — cascade).
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

        $pairs = [
            ['key' => 'fi', 'subId' => $subscription->fi_sub_id],
            ['key' => 'nl', 'subId' => $subscription->nl_sub_id],
        ];

        foreach ($pairs as $row) {
            $bundleKey = $row['key'];
            $node = $nodes[$bundleKey] ?? null;
            if (! is_array($node)) {
                throw new XuiPanelException("Нет конфигурации узла: {$bundleKey}");
            }

            $base = (string) ($node['panel_base'] ?? '');
            $inboundId = (int) ($node['inbound_id'] ?? 0);
            if ($base === '' || $inboundId < 1) {
                throw new XuiPanelException("Неверные panel_base / inbound для {$bundleKey}");
            }

            $email = $this->clientEmail($node, (string) $row['subId']);

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

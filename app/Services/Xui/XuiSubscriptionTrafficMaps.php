<?php

namespace App\Services\Xui;

use Throwable;

/**
 * Трафик клиентов по subId с каждой панели 3x-ui (сопоставление email ↔ subId из settings).
 */
final class XuiSubscriptionTrafficMaps
{
    /**
     * @return array{
     *     maps: array<string, array<string, array{up: int, down: int, total: int}>>,
     *     errors: list<string>,
     * }
     */
    public function fetch(): array
    {
        $user = (string) config('xui.panel_username');
        $pass = (string) config('xui.panel_password');
        $order = config('xui.bundle_order', ['fi', 'nl']);
        $nodes = config('xui.nodes', []);

        $maps = [];
        $errors = [];

        if ($user === '' || $pass === '') {
            $errors[] = 'Не заданы XUI_PANEL_USER / XUI_PANEL_PASSWORD';

            return ['maps' => $maps, 'errors' => $errors];
        }

        foreach ($order as $bundleKey) {
            $maps[$bundleKey] = [];
        }

        foreach ($order as $bundleKey) {
            $node = $nodes[$bundleKey] ?? null;
            if (! is_array($node)) {
                $errors[] = "Узел «{$bundleKey}»: нет конфигурации";

                continue;
            }

            $base = (string) ($node['panel_base'] ?? '');
            if ($base === '') {
                $errors[] = "Узел «{$bundleKey}»: пустой panel_base";

                continue;
            }

            $inboundId = (int) ($node['inbound_id'] ?? 0);
            if ($inboundId < 1) {
                $errors[] = "Узел «{$bundleKey}»: неверный inbound_id";

                continue;
            }

            try {
                $client = new XuiPanelClient($base);
                $client->login($user, $pass);
                $list = $client->getInboundsList();
                $inbound = $this->findInbound($list, $inboundId);
                if ($inbound === null) {
                    $errors[] = "Узел «{$bundleKey}»: inbound #{$inboundId} не найден";

                    continue;
                }
                $maps[$bundleKey] = $this->extractSubIdTrafficMap($inbound);
            } catch (Throwable $e) {
                $errors[] = "Узел «{$bundleKey}»: ".$e->getMessage();
            }
        }

        return ['maps' => $maps, 'errors' => $errors];
    }

    /**
     * @param  list<array<string, mixed>>  $list
     */
    private function findInbound(array $list, int $inboundId): ?array
    {
        foreach ($list as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ((int) ($row['id'] ?? 0) === $inboundId) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $inbound
     * @return array<string, array{up: int, down: int, total: int}>
     */
    private function extractSubIdTrafficMap(array $inbound): array
    {
        $settings = json_decode((string) ($inbound['settings'] ?? ''), true);
        if (! is_array($settings)) {
            return [];
        }

        $clients = $settings['clients'] ?? [];
        if (! is_array($clients)) {
            return [];
        }

        $byEmail = [];
        $stats = $inbound['clientStats'] ?? [];
        if (is_array($stats)) {
            foreach ($stats as $stat) {
                if (! is_array($stat)) {
                    continue;
                }
                $email = (string) ($stat['email'] ?? '');
                if ($email === '') {
                    continue;
                }
                $byEmail[$email] = [
                    'up' => (int) ($stat['up'] ?? 0),
                    'down' => (int) ($stat['down'] ?? 0),
                    'total' => (int) ($stat['total'] ?? 0),
                ];
            }
        }

        $out = [];
        foreach ($clients as $c) {
            if (! is_array($c)) {
                continue;
            }
            $subId = (string) ($c['subId'] ?? '');
            $email = (string) ($c['email'] ?? '');
            if ($subId === '' || $email === '' || ! isset($byEmail[$email])) {
                continue;
            }
            $out[$subId] = $byEmail[$email];
        }

        return $out;
    }
}

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
        $order = config('xui.bundle_order', ['fi', 'nl']);
        $nodes = config('xui.nodes', []);

        $maps = [];
        $errors = [];

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
            $user = (string) ($node['panel_username'] ?? config('xui.panel_username', ''));
            $pass = (string) ($node['panel_password'] ?? config('xui.panel_password', ''));
            if ($user === '' || $pass === '') {
                $errors[] = "Узел «{$bundleKey}»: не заданы логин/пароль панели";

                continue;
            }

            try {
                $client = new XuiPanelClient($base);
                $client->login($user, $pass);
                $inbound = $client->getInboundById($inboundId);
                if ($inbound === []) {
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

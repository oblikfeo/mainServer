<?php

namespace App\Services\Xui;

use App\Models\Subscription;
use Throwable;

/**
 * Справочно: уникальные IP по подписке (объединение clientIps FI+NL) для отчёта в админке.
 */
final class XuiSubscriptionConnectionInspector
{
    /**
     * @return array{
     *     by_subscription_id: array<int, array{
     *         online_ip_count: int,
     *         limit: int,
     *         over: bool,
     *         fi_email: ?string,
     *         nl_email: ?string
     *     }>,
     *     errors: list<string>
     * }
     */
    public function inspectAllActive(): array
    {
        $errors = [];
        $byId = [];

        $user = (string) config('xui.panel_username');
        $pass = (string) config('xui.panel_password');
        if ($user === '' || $pass === '') {
            $errors[] = 'Не заданы XUI_PANEL_USER / XUI_PANEL_PASSWORD';

            return ['by_subscription_id' => $byId, 'errors' => $errors];
        }

        $nowMs = (int) (now()->getTimestamp() * 1000);
        $subs = Subscription::query()
            ->where(function ($q) use ($nowMs) {
                $q->where('expiry_ms', '<=', 0)
                    ->orWhere('expiry_ms', '>', $nowMs);
            })
            ->whereNotNull('fi_sub_id')
            ->whereNotNull('nl_sub_id')
            ->where('fi_sub_id', '!=', '')
            ->where('nl_sub_id', '!=', '')
            ->get();

        if ($subs->isEmpty()) {
            return ['by_subscription_id' => $byId, 'errors' => $errors];
        }

        $fiCtx = $this->buildNodeContext('fi', $errors);
        $nlCtx = $this->buildNodeContext('nl', $errors);

        if ($fiCtx === null || $nlCtx === null) {
            return ['by_subscription_id' => $byId, 'errors' => $errors];
        }

        $fiIpCache = [];
        $nlIpCache = [];

        foreach ($subs as $sub) {
            $fiEmail = $fiCtx['sub_to_email'][$sub->fi_sub_id] ?? null;
            $nlEmail = $nlCtx['sub_to_email'][$sub->nl_sub_id] ?? null;

            $ips = [];
            if (is_string($fiEmail) && $fiEmail !== '') {
                foreach ($this->cachedIps($fiCtx['client'], $fiEmail, $fiIpCache) as $ip) {
                    $ips[$ip] = true;
                }
            }
            if (is_string($nlEmail) && $nlEmail !== '') {
                foreach ($this->cachedIps($nlCtx['client'], $nlEmail, $nlIpCache) as $ip) {
                    $ips[$ip] = true;
                }
            }

            $count = count($ips);
            $limit = max(0, (int) $sub->devices);
            $over = $limit > 0 && $count > $limit;

            $byId[(int) $sub->id] = [
                'online_ip_count' => $count,
                'limit' => $limit,
                'over' => $over,
                'fi_email' => $fiEmail,
                'nl_email' => $nlEmail,
            ];
        }

        return ['by_subscription_id' => $byId, 'errors' => $errors];
    }

    /**
     * @param  array<string, list<string>>  $cache
     * @return list<string>
     */
    private function cachedIps(XuiPanelClient $client, string $email, array &$cache): array
    {
        if (! isset($cache[$email])) {
            $cache[$email] = $client->getClientIpsNormalized($email);
        }

        return $cache[$email];
    }

    /**
     * @param  list<string>  $errors
     * @return array{client: XuiPanelClient, inbound_id: int, sub_to_email: array<string, string>}|null
     */
    private function buildNodeContext(string $bundleKey, array &$errors): ?array
    {
        $node = config('xui.nodes.'.$bundleKey, []);
        if (! is_array($node)) {
            $errors[] = "Узел «{$bundleKey}»: нет конфигурации";

            return null;
        }

        $base = (string) ($node['panel_base'] ?? '');
        $inboundId = (int) ($node['inbound_id'] ?? 0);
        if ($base === '' || $inboundId < 1) {
            $errors[] = "Узел «{$bundleKey}»: пустой panel_base или inbound_id";

            return null;
        }

        $user = (string) config('xui.panel_username');
        $pass = (string) config('xui.panel_password');

        try {
            $client = new XuiPanelClient($base);
            $client->login($user, $pass);
            $inbound = $client->getInboundById($inboundId);
            if ($inbound === []) {
                $errors[] = "Узел «{$bundleKey}»: inbound #{$inboundId} пустой ответ";

                return null;
            }

            $map = $this->subIdToEmailFromInbound($inbound);

            return [
                'client' => $client,
                'inbound_id' => $inboundId,
                'sub_to_email' => $map,
            ];
        } catch (Throwable $e) {
            $errors[] = "Узел «{$bundleKey}»: ".$e->getMessage();

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $inbound
     * @return array<string, string>
     */
    private function subIdToEmailFromInbound(array $inbound): array
    {
        $settings = json_decode((string) ($inbound['settings'] ?? ''), true);
        if (! is_array($settings)) {
            return [];
        }

        $clients = $settings['clients'] ?? [];
        if (! is_array($clients)) {
            return [];
        }

        $map = [];
        foreach ($clients as $c) {
            if (! is_array($c)) {
                continue;
            }
            $sid = (string) ($c['subId'] ?? '');
            $em = (string) ($c['email'] ?? '');
            if ($sid !== '' && $em !== '') {
                $map[$sid] = $em;
            }
        }

        return $map;
    }
}

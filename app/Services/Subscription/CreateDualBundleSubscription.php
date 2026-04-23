<?php

namespace App\Services\Subscription;

use App\Models\IssuedKey;
use App\Models\Subscription;
use App\Services\Xui\XuiPanelClient;
use App\Services\Xui\XuiPanelException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

final class CreateDualBundleSubscription
{
    private const BYTES_PER_GB = 1_073_741_824;

    /**
     * @throws XuiPanelException
     */
    public function create(int $devices, int $days, int $quotaGb, ?int $userId = null): CreatedSubscriptionResult
    {
        $order = config('xui.bundle_order', ['fi', 'nl']);
        $nodes = config('xui.nodes', []);

        $expiryMs = (int) ((time() + $days * 86400) * 1000);

        $nodeCount = count($order);
        if ($nodeCount < 1) {
            throw new XuiPanelException('Пустой список узлов в config/xui.php (bundle_order)');
        }
        $quotaBytes = $quotaGb * self::BYTES_PER_GB;
        $bytesPerNode = max(1, intdiv($quotaBytes, $nodeCount));

        $subIds = [];
        $createdClients = [];

        foreach ($order as $bundleKey) {
            $node = $nodes[$bundleKey] ?? null;
            if (! is_array($node)) {
                throw new XuiPanelException("Нет конфигурации узла: {$bundleKey}");
            }

            $base = (string) ($node['panel_base'] ?? '');
            if ($base === '') {
                throw new XuiPanelException("Пустой panel_base для {$bundleKey} (XUI_*_BASE)");
            }

            $user = (string) ($node['panel_username'] ?? '');
            $pass = (string) ($node['panel_password'] ?? '');
            if ($user === '' || $pass === '') {
                throw new XuiPanelException(
                    "Не заданы креды для узла «{$bundleKey}» (XUI_".strtoupper($bundleKey)."_USER/PASSWORD либо XUI_PANEL_USER/PASSWORD)"
                );
            }

            $inboundId = (int) ($node['inbound_id'] ?? 0);
            if ($inboundId < 1) {
                throw new XuiPanelException("Неверный inbound для {$bundleKey}");
            }

            $prefix = (string) ($node['client_email_prefix'] ?? $bundleKey);
            $flow = (string) ($node['client_flow'] ?? 'xtls-rprx-vision');
            $subId = bin2hex(random_bytes(8));
            $email = $prefix.'-'.substr($subId, 0, 10);
            $uid = (string) Str::uuid();
            $currentClient = [
                'bundle' => $bundleKey,
                'base' => $base,
                'user' => $user,
                'pass' => $pass,
                'inboundId' => $inboundId,
                'email' => $email,
            ];

            $clientDef = [
                'id' => $uid,
                'email' => $email,
                'flow' => $flow,
                'limitIp' => max(0, $devices),
                'totalGB' => $bytesPerNode,
                'expiryTime' => $expiryMs,
                'enable' => true,
                'tgId' => 0,
                'subId' => $subId,
            ];

            try {
                $client = new XuiPanelClient($base);
                $client->login($user, $pass);
                $client->addInboundClient($inboundId, $clientDef);
                $client->restartXray();
                $createdClients[] = $currentClient;
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                $rollbackMessage = $this->rollbackClients(
                    array_merge($createdClients, [$currentClient])
                );
                if ($rollbackMessage !== '') {
                    $msg .= " | Откат: {$rollbackMessage}";
                }
                throw new XuiPanelException(
                    "Узел «{$bundleKey}»: {$msg}",
                    previous: $e
                );
            }

            $subIds[$bundleKey] = $subId;
        }

        $wifiSubId = (string) ($subIds['wifi'] ?? '');
        $fiSubId = (string) ($subIds['fi'] ?? '');
        $nlSubId = (string) ($subIds['nl'] ?? '');
        if ($fiSubId === '' || $nlSubId === '') {
            throw new XuiPanelException('Внутренняя ошибка: не все subId созданы');
        }

        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        $subscription = Subscription::query()->create([
            'user_id' => $userId,
            'token' => $token,
            'wifi_sub_id' => $wifiSubId !== '' ? $wifiSubId : null,
            'fi_sub_id' => $fiSubId,
            'nl_sub_id' => $nlSubId,
            'quota_gb' => $quotaGb,
            'expiry_ms' => $expiryMs,
            'devices' => $devices,
        ]);

        if ($wifiSubId !== '') {
            IssuedKey::query()->create(['bundle_id' => 'wifi', 'subscription_id' => $subscription->id]);
        }
        IssuedKey::query()->create(['bundle_id' => 'fi', 'subscription_id' => $subscription->id]);
        IssuedKey::query()->create(['bundle_id' => 'nl', 'subscription_id' => $subscription->id]);

        $publicBase = rtrim((string) config('app.url'), '/');
        $subscriptionUrl = $publicBase.'/sub/'.$token;

        $decoded = $this->decodeLinesForSubscription($subscription);

        return new CreatedSubscriptionResult(
            $subscription,
            $subscriptionUrl,
            $decoded['wifi'],
            $decoded['fi'],
            $decoded['nl'],
            $decoded['warning'],
        );
    }

    /**
     * @return array{wifi: string, fi: string, nl: string, warning: ?string}
     */
    public function decodeLinesForSubscription(Subscription $subscription): array
    {
        $nodes = config('xui.nodes', []);
        $order = config('xui.bundle_order', ['wifi', 'fi', 'nl']);
        $subDesc = (string) config('xui.vless_server_description', '');
        $subFmt = (string) config('xui.vless_server_description_format', 'dual');

        $out = ['wifi' => '', 'fi' => '', 'nl' => '', 'warning' => null];
        $missing = [];

        foreach ($order as $key) {
            if (! array_key_exists($key, $out) || $key === 'warning') {
                continue;
            }
            $node = $nodes[$key] ?? [];
            $subIdField = $key.'_sub_id';
            $subId = (string) ($subscription->$subIdField ?? '');
            if ($subId === '') {
                continue;
            }

            try {
                $raw = $this->fetchSubRaw(
                    (string) ($node['sub_origin'] ?? ''),
                    $subId,
                    (string) ($node['pub_host'] ?? '')
                );
                $line = VlessSubscriptionHelper::extractVlessLineFromSubscriptionBody($raw);
                if ($line === '') {
                    $missing[] = strtoupper($key);
                    continue;
                }
                $line = VlessSubscriptionHelper::ensureRealitySid(
                    $line,
                    (string) ($node['reality_sid'] ?? '')
                );
                $out[$key] = VlessSubscriptionHelper::setVlessFragment(
                    $line,
                    (string) ($node['vless_display_name'] ?? strtoupper($key)),
                    $subDesc,
                    $subFmt
                );
            } catch (Throwable $e) {
                $missing[] = strtoupper($key).' ('.$e->getMessage().')';
            }
        }

        if ($missing !== []) {
            $out['warning'] = 'Не удалось получить строку vless:// с узлов: '.implode(', ', $missing);
        }

        return $out;
    }

    /**
     * @param  list<array{bundle: string, base: string, user: string, pass: string, inboundId: int, email: string}>  $clients
     */
    private function rollbackClients(array $clients): string
    {
        $errors = [];
        $seen = [];

        foreach ($clients as $item) {
            $base = (string) ($item['base'] ?? '');
            $user = (string) ($item['user'] ?? '');
            $pass = (string) ($item['pass'] ?? '');
            $inboundId = (int) ($item['inboundId'] ?? 0);
            $email = (string) ($item['email'] ?? '');
            $bundle = (string) ($item['bundle'] ?? '?');
            if ($base === '' || $inboundId < 1 || $email === '' || $user === '' || $pass === '') {
                continue;
            }

            $uniq = $base.'|'.$inboundId.'|'.$email;
            if (isset($seen[$uniq])) {
                continue;
            }
            $seen[$uniq] = true;

            try {
                $client = new XuiPanelClient($base);
                $client->login($user, $pass);
                $client->deleteInboundClientByEmail($inboundId, $email);
                $client->restartXray();
            } catch (Throwable $e) {
                $errors[] = $bundle.'('.$email.'): '.$e->getMessage();
            }
        }

        return implode('; ', $errors);
    }

    private function fetchSubRaw(string $subOrigin, string $subId, string $pubHost): string
    {
        $url = rtrim($subOrigin, '/').'/sub/'.rawurlencode((string) $subId);
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0',
            'Accept' => '*/*',
            'Accept-Encoding' => 'identity',
        ];
        $pubHost = trim($pubHost);
        if ($pubHost !== '') {
            $headers['X-Forwarded-Host'] = $pubHost;
            $headers['X-Real-IP'] = $pubHost;
        }
        $response = Http::withoutVerifying()
            ->withHeaders($headers)
            ->connectTimeout(12)
            ->timeout(28)
            ->get($url);
        if (! $response->successful()) {
            throw new XuiPanelException('Подписка '.$subId.': HTTP '.$response->status());
        }

        return trim($response->body());
    }
}

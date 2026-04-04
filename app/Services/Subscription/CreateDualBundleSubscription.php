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
    public function create(int $devices, int $days, int $quotaGb): CreatedSubscriptionResult
    {
        $user = (string) config('xui.panel_username');
        $pass = (string) config('xui.panel_password');
        $order = config('xui.bundle_order', ['fi', 'nl']);
        $nodes = config('xui.nodes', []);

        if ($user === '' || $pass === '') {
            throw new XuiPanelException('Не заданы XUI_PANEL_USER / XUI_PANEL_PASSWORD');
        }

        $expiryMs = (int) ((time() + $days * 86400) * 1000);

        $nodeCount = count($order);
        if ($nodeCount < 1) {
            throw new XuiPanelException('Пустой список узлов в config/xui.php (bundle_order)');
        }
        $quotaBytes = $quotaGb * self::BYTES_PER_GB;
        $bytesPerNode = max(1, intdiv($quotaBytes, $nodeCount));

        $subIds = [];

        foreach ($order as $bundleKey) {
            $node = $nodes[$bundleKey] ?? null;
            if (! is_array($node)) {
                throw new XuiPanelException("Нет конфигурации узла: {$bundleKey}");
            }

            $base = (string) ($node['panel_base'] ?? '');
            if ($base === '') {
                throw new XuiPanelException("Пустой panel_base для {$bundleKey} (XUI_*_BASE)");
            }

            $inboundId = (int) ($node['inbound_id'] ?? 0);
            if ($inboundId < 1) {
                throw new XuiPanelException("Неверный inbound для {$bundleKey}");
            }

            $prefix = (string) ($node['client_email_prefix'] ?? $bundleKey);
            $subId = bin2hex(random_bytes(8));
            $email = $prefix.'-'.substr($subId, 0, 10);
            $uid = (string) Str::uuid();

            $clientDef = [
                'id' => $uid,
                'email' => $email,
                'flow' => 'xtls-rprx-vision',
                'limitIp' => $devices,
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
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                throw new XuiPanelException(
                    "Узел «{$bundleKey}»: {$msg}",
                    previous: $e
                );
            }

            $subIds[$bundleKey] = $subId;
        }

        $fiSubId = (string) ($subIds['fi'] ?? '');
        $nlSubId = (string) ($subIds['nl'] ?? '');
        if ($fiSubId === '' || $nlSubId === '') {
            throw new XuiPanelException('Внутренняя ошибка: не все subId созданы');
        }

        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        $subscription = Subscription::query()->create([
            'token' => $token,
            'fi_sub_id' => $fiSubId,
            'nl_sub_id' => $nlSubId,
            'quota_gb' => $quotaGb,
            'expiry_ms' => $expiryMs,
            'devices' => $devices,
        ]);

        IssuedKey::query()->create(['bundle_id' => 'fi', 'subscription_id' => $subscription->id]);
        IssuedKey::query()->create(['bundle_id' => 'nl', 'subscription_id' => $subscription->id]);

        $publicBase = rtrim((string) config('app.url'), '/');
        $subscriptionUrl = $publicBase.'/sub/'.$token;

        $decoded = $this->decodeLinesForSubscription($subscription);

        return new CreatedSubscriptionResult(
            $subscription,
            $subscriptionUrl,
            $decoded['fi'],
            $decoded['nl'],
            $decoded['warning'],
        );
    }

    /**
     * @return array{fi: string, nl: string, warning: ?string}
     */
    public function decodeLinesForSubscription(Subscription $subscription): array
    {
        $nodes = config('xui.nodes', []);
        $fiNode = $nodes['fi'] ?? [];
        $nlNode = $nodes['nl'] ?? [];

        try {
            $fiRaw = $this->fetchSubRaw(
                (string) ($fiNode['sub_origin'] ?? ''),
                $subscription->fi_sub_id,
                (string) ($fiNode['pub_host'] ?? '')
            );
            $nlRaw = $this->fetchSubRaw(
                (string) ($nlNode['sub_origin'] ?? ''),
                $subscription->nl_sub_id,
                (string) ($nlNode['pub_host'] ?? '')
            );

            $fiLine = VlessSubscriptionHelper::extractVlessLineFromSubscriptionBody($fiRaw);
            $nlLine = VlessSubscriptionHelper::extractVlessLineFromSubscriptionBody($nlRaw);

            return [
                'fi' => VlessSubscriptionHelper::setVlessFragment(
                    $fiLine,
                    (string) ($fiNode['vless_display_name'] ?? 'FI')
                ),
                'nl' => VlessSubscriptionHelper::setVlessFragment(
                    $nlLine,
                    (string) ($nlNode['vless_display_name'] ?? 'NL')
                ),
                'warning' => ($fiLine === '' || $nlLine === '')
                    ? 'В ответе одной из панелей не найдена строка vless://'
                    : null,
            ];
        } catch (Throwable $e) {
            return [
                'fi' => '',
                'nl' => '',
                'warning' => $e->getMessage(),
            ];
        }
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

<?php

namespace App\Services\TestKeys;

use App\Models\TestKey;
use App\Models\User;
use App\Services\Xui\XuiPanelClient;
use App\Services\Xui\XuiPanelException;
use Illuminate\Support\Str;

final class TestKeyManager
{
    private const BYTES_PER_GB = 1_073_741_824;

    private function assertConfigured(): void
    {
        $base = (string) config('test_keys.panel_base');
        $user = (string) config('test_keys.panel_username');
        $pass = (string) config('test_keys.panel_password');
        $inboundId = (int) config('test_keys.inbound_id');
        $host = (string) config('test_keys.public_host');
        $sni = (string) config('test_keys.reality_sni');
        $pbk = (string) config('test_keys.reality_public_key');
        $sid = (string) config('test_keys.reality_short_id');

        if ($base === '' || $user === '' || $pass === '' || $inboundId < 1 || $host === '' || $sni === '' || $pbk === '' || $sid === '') {
            throw new \RuntimeException('Тестовая связка не настроена (TEST_KEYS_* в .env).');
        }
    }

    public function issueForUser(User $user, ?int $hours = null): TestKey
    {
        $this->assertConfigured();

        $hours = $hours ?? (int) config('test_keys.default_hours', 8);
        $hours = max(1, min(48, (int) $hours));

        $now = now();
        $expiresAt = $now->clone()->addHours($hours);
        $expiryMs = (int) ($expiresAt->getTimestamp() * 1000);

        $clientUuid = (string) Str::uuid();
        $subId = bin2hex(random_bytes(8));

        $emailPrefix = 'test';
        $panelEmail = $emailPrefix.'-'.$user->id.'-'.substr($subId, 0, 10);

        $limitIp = max(0, (int) config('test_keys.default_limit_ip', 1));
        $quotaGb = max(1, (int) config('test_keys.default_quota_gb', 50));
        $totalBytes = $quotaGb * self::BYTES_PER_GB;

        $flow = (string) config('test_keys.flow', 'xtls-rprx-vision');

        $clientDef = [
            'id' => $clientUuid,
            'email' => $panelEmail,
            'flow' => $flow,
            'limitIp' => $limitIp,
            'totalGB' => $totalBytes,
            'expiryTime' => $expiryMs,
            'enable' => true,
            'tgId' => 0,
            'subId' => $subId,
        ];

        $panel = new XuiPanelClient((string) config('test_keys.panel_base'));
        $panel->login((string) config('test_keys.panel_username'), (string) config('test_keys.panel_password'));

        try {
            $panel->addInboundClient((int) config('test_keys.inbound_id'), $clientDef);
            $panel->restartXray();
        } catch (XuiPanelException $e) {
            throw $e;
        }

        $vlessUrl = $this->buildVlessUrl(
            clientUuid: $clientUuid,
            label: 'LTE + WIFI 🇷🇺',
        );

        $token = $this->generateUniqueToken();
        $subscriptionUrl = rtrim((string) config('app.url'), '/').'/sub/'.$token;

        return TestKey::query()->create([
            'user_id' => $user->id,
            'client_uuid' => $clientUuid,
            'panel_email' => $panelEmail,
            'panel_sub_id' => $subId,
            'token' => $token,
            'issued_at' => $now,
            'expires_at' => $expiresAt,
            'vless_url' => $vlessUrl,
            'subscription_url' => $subscriptionUrl,
            'quota_gb' => $quotaGb,
            'limit_ip' => $limitIp,
        ]);
    }

    public function revoke(TestKey $key, string $reason = 'manual'): void
    {
        if ($key->revoked_at !== null) {
            return;
        }

        $this->assertConfigured();

        $panel = new XuiPanelClient((string) config('test_keys.panel_base'));
        $panel->login((string) config('test_keys.panel_username'), (string) config('test_keys.panel_password'));

        try {
            $panel->deleteInboundClientByEmail((int) config('test_keys.inbound_id'), $key->panel_email);
            $panel->restartXray();
            $key->panel_deleted_at = now();
        } catch (XuiPanelException) {
            // не роняем revoke: ключ считаем снятым логически; панельную чистку добьём повторно
        }

        $key->revoked_at = now();
        $key->revoked_reason = $reason;
        $key->save();
    }

    public function cleanupExpired(): int
    {
        $n = 0;
        $keys = TestKey::query()
            ->whereNull('revoked_at')
            ->where('expires_at', '<=', now())
            ->orderBy('expires_at')
            ->limit(200)
            ->get();

        foreach ($keys as $k) {
            $this->revoke($k, 'expired');
            $n++;
        }

        return $n;
    }

    private function buildVlessUrl(string $clientUuid, string $label): string
    {
        $host = (string) config('test_keys.public_host');
        $port = (int) config('test_keys.public_port', 443);
        $pbk = (string) config('test_keys.reality_public_key');
        $sni = (string) config('test_keys.reality_sni');
        $sid = (string) config('test_keys.reality_short_id');
        $flow = (string) config('test_keys.flow', 'xtls-rprx-vision');
        $fp = (string) config('test_keys.fingerprint', 'chrome');

        $params = http_build_query([
            'type' => 'tcp',
            'security' => 'reality',
            'pbk' => $pbk,
            'fp' => $fp,
            'sni' => $sni,
            'sid' => $sid,
            'flow' => $flow,
        ]);

        return "vless://{$clientUuid}@{$host}:{$port}?{$params}#".rawurlencode($label);
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
        } while (TestKey::query()->where('token', $token)->exists());

        return $token;
    }
}


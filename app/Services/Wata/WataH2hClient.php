<?php

namespace App\Services\Wata;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class WataH2hClient
{
    private function http(): PendingRequest
    {
        $base = (string) config('wata.base_url');
        $token = (string) config('wata.access_token');
        if ($base === '' || $token === '') {
            throw new RuntimeException('WATA: не заданы WATA_BASE_URL / WATA_ACCESS_TOKEN');
        }

        return Http::baseUrl($base)
            ->acceptJson()
            ->asJson()
            ->withToken($token)
            ->timeout(60);
    }

    /**
     * @return array{id: string, url: string, status: string, orderId: ?string}
     */
    public function createPaymentLink(array $payload): array
    {
        $r = $this->http()->post('links', $payload);
        if (! $r->successful()) {
            throw new RuntimeException('WATA links: HTTP '.$r->status().' '.$r->body());
        }
        $j = $r->json();
        if (! is_array($j)) {
            throw new RuntimeException('WATA links: некорректный JSON');
        }
        $id = (string) ($j['id'] ?? '');
        $url = (string) ($j['url'] ?? '');
        $status = (string) ($j['status'] ?? '');

        if ($id === '' || $url === '') {
            throw new RuntimeException('WATA links: в ответе нет id/url');
        }

        return [
            'id' => $id,
            'url' => $url,
            'status' => $status,
            'orderId' => isset($j['orderId']) ? (string) $j['orderId'] : null,
        ];
    }

    /**
     * Публичный ключ для проверки подписи webhook (PKCS1/PEM).
     */
    public function getWebhookPublicKeyPem(): string
    {
        $ttl = 3600;

        return (string) Cache::remember('wata_webhook_public_key_v1', $ttl, function () {
            $r = $this->http()->get('public-key');
            if (! $r->successful()) {
                throw new RuntimeException('WATA public-key: HTTP '.$r->status());
            }
            $j = $r->json();
            if (! is_array($j)) {
                throw new RuntimeException('WATA public-key: некорректный JSON');
            }
            $pem = (string) ($j['value'] ?? '');
            if ($pem === '') {
                throw new RuntimeException('WATA public-key: пустое значение');
            }

            return $pem;
        });
    }
}


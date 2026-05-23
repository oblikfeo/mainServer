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
     * @return array{transactionId: string, transactionStatus: string, sbpLink: string}
     */
    public function createSbpTransaction(array $payload): array
    {
        $r = $this->http()->post('payments/sbp', $payload);
        if (! $r->successful()) {
            throw new RuntimeException('WATA payments/sbp: HTTP '.$r->status().' '.$r->body());
        }
        $j = $r->json();
        if (! is_array($j)) {
            throw new RuntimeException('WATA payments/sbp: некорректный JSON');
        }
        $transactionId = (string) ($j['transactionId'] ?? '');
        $sbpLink = trim((string) ($j['sbpLink'] ?? ''));
        if ($transactionId === '' || $sbpLink === '') {
            throw new RuntimeException('WATA payments/sbp: в ответе нет transactionId/sbpLink');
        }

        return [
            'transactionId' => $transactionId,
            'transactionStatus' => (string) ($j['transactionStatus'] ?? 'Pending'),
            'sbpLink' => $sbpLink,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getTransaction(string $transactionUuid): array
    {
        $id = trim($transactionUuid);
        if ($id === '') {
            throw new RuntimeException('WATA transactions: пустой UUID');
        }

        $r = $this->http()->get('transactions/'.$id);
        if (! $r->successful()) {
            throw new RuntimeException('WATA transactions: HTTP '.$r->status().' '.$r->body());
        }
        $j = $r->json();
        if (! is_array($j)) {
            throw new RuntimeException('WATA transactions: некорректный JSON');
        }

        return $j;
    }

    /**
     * Публичный ключ для проверки подписи webhook (PKCS1/PEM).
     * В доке WATA запрос к public-key без Authorization — делаем так же, при 401 пробуем с токеном.
     */
    public function getWebhookPublicKeyPem(): string
    {
        $ttl = 3600;

        return (string) Cache::remember('wata_webhook_public_key_v2', $ttl, function () {
            $base = rtrim((string) config('wata.base_url'), '/').'/';
            $fetch = function (bool $withToken) use ($base) {
                $req = Http::baseUrl($base)
                    ->acceptJson()
                    ->timeout(30);
                if ($withToken) {
                    $token = (string) config('wata.access_token');
                    if ($token !== '') {
                        $req = $req->withToken($token);
                    }
                }

                return $req->get('public-key');
            };

            $r = $fetch(false);
            if ($r->status() === 401) {
                $r = $fetch(true);
            }
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


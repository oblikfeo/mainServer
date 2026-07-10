<?php

namespace App\Services\Platega;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class PlategaClient
{
    public function isConfigured(): bool
    {
        return trim((string) config('platega.merchant_id')) !== ''
            && trim((string) config('platega.secret')) !== '';
    }

    private function http(): PendingRequest
    {
        $merchantId = trim((string) config('platega.merchant_id'));
        $secret = trim((string) config('platega.secret'));
        if ($merchantId === '' || $secret === '') {
            throw new RuntimeException('Platega: не заданы PLATEGA_MERCHANT_ID / PLATEGA_SECRET');
        }

        return Http::baseUrl((string) config('platega.base_url'))
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'X-MerchantId' => $merchantId,
                'X-Secret' => $secret,
            ])
            ->timeout(60);
    }

    /**
     * @param  array{userId: string, userName: string}  $metadata
     * @return array{transactionId: string, status: string, url: string, expiresIn: string}
     */
    public function createTransaction(
        int $amountRub,
        string $description,
        string $returnUrl,
        string $failedUrl,
        string $payload,
        ?int $paymentMethod,
        array $metadata,
    ): array {
        $body = [
            'paymentDetails' => [
                'amount' => $amountRub,
                'currency' => 'RUB',
            ],
            'description' => $description,
            'return' => $returnUrl,
            'failedUrl' => $failedUrl,
            'payload' => $payload,
            'metadata' => [
                'userId' => (string) ($metadata['userId'] ?? ''),
                'userName' => (string) ($metadata['userName'] ?? ''),
            ],
        ];

        if ($paymentMethod !== null && $paymentMethod > 0) {
            $body['paymentMethod'] = $paymentMethod;
        }

        $r = $this->http()->post('v2/transaction/process', $body);
        if (! $r->successful()) {
            throw new RuntimeException('Platega create: HTTP '.$r->status().' '.$r->body());
        }

        $j = $r->json();
        if (! is_array($j)) {
            throw new RuntimeException('Platega create: некорректный JSON');
        }

        $transactionId = (string) ($j['transactionId'] ?? '');
        $url = (string) ($j['url'] ?? $j['redirect'] ?? '');
        if ($transactionId === '' || $url === '') {
            throw new RuntimeException('Platega create: нет transactionId/url');
        }

        return [
            'transactionId' => $transactionId,
            'status' => (string) ($j['status'] ?? 'PENDING'),
            'url' => $url,
            'expiresIn' => (string) ($j['expiresIn'] ?? ''),
            'raw' => $j,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getTransactionStatus(string $transactionId): array
    {
        $id = trim($transactionId);
        if ($id === '') {
            throw new RuntimeException('Platega status: пустой transactionId');
        }

        $r = $this->http()->get('transaction/'.$id);
        if (! $r->successful()) {
            throw new RuntimeException('Platega status: HTTP '.$r->status().' '.$r->body());
        }

        $j = $r->json();
        if (! is_array($j)) {
            throw new RuntimeException('Platega status: некорректный JSON');
        }

        return $j;
    }
}

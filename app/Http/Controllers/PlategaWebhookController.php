<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Services\Payments\FulfillPaidPaymentOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class PlategaWebhookController extends Controller
{
    public function __invoke(Request $request, FulfillPaidPaymentOrder $fulfill): Response
    {
        if (! $this->verifyHeaders($request)) {
            Log::warning('Platega webhook: bad auth headers');

            return response('', 403);
        }

        $payload = $request->json()->all();
        if (! is_array($payload) || $payload === []) {
            return response('', 200);
        }

        $transactionId = isset($payload['id']) ? (string) $payload['id'] : '';
        $status = strtoupper(isset($payload['status']) ? (string) $payload['status'] : '');
        $payloadOrderId = isset($payload['payload']) ? (string) $payload['payload'] : '';

        if ($transactionId === '' && $payloadOrderId === '') {
            return response('', 200);
        }

        $order = null;
        if ($payloadOrderId !== '') {
            $order = PaymentOrder::query()
                ->where('order_id', $payloadOrderId)
                ->where('provider', 'platega')
                ->first();
        }
        if ($order === null && $transactionId !== '') {
            $order = PaymentOrder::query()
                ->where('provider_transaction_id', $transactionId)
                ->where('provider', 'platega')
                ->first();
        }

        if ($order === null) {
            Log::warning('Platega webhook: order not found', [
                'transaction_id' => $transactionId,
                'payload' => $payloadOrderId,
            ]);

            return response('', 200);
        }

        try {
            return DB::transaction(function () use ($order, $payload, $transactionId, $status, $fulfill): Response {
                /** @var PaymentOrder|null $locked */
                $locked = PaymentOrder::query()->whereKey($order->id)->lockForUpdate()->first();
                if (! $locked) {
                    return response('', 200);
                }

                if ($transactionId !== '') {
                    $locked->provider_transaction_id = $transactionId;
                }
                $locked->provider_payload = $payload;

                if ($status === 'CANCELED' || $status === 'CHARGEBACKED') {
                    if ($locked->status !== 'paid') {
                        $locked->status = 'declined';
                        $locked->declined_at = now();
                    }
                    $locked->save();

                    return response('', 200);
                }

                if ($status !== 'CONFIRMED') {
                    if ($locked->status === 'created') {
                        $locked->status = 'pending';
                    }
                    $locked->save();

                    return response('', 200);
                }

                if ($locked->status === 'paid') {
                    return response('', 200);
                }

                $fulfill->fulfill($locked);

                return response('', 200);
            });
        } catch (\Throwable $e) {
            Log::error('Platega webhook: paid handler failed: '.$e->getMessage());

            return response('', 500);
        }
    }

    private function verifyHeaders(Request $request): bool
    {
        $expectedMerchant = trim((string) config('platega.merchant_id'));
        $expectedSecret = trim((string) config('platega.secret'));
        if ($expectedMerchant === '' || $expectedSecret === '') {
            return false;
        }

        $merchant = (string) $request->header('X-MerchantId', '');
        $secret = (string) $request->header('X-Secret', '');

        return hash_equals($expectedMerchant, $merchant) && hash_equals($expectedSecret, $secret);
    }
}

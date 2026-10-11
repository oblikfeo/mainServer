<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Services\Account\AccountOrderFactory;
use App\Services\Account\AccountSubscription;
use App\Services\Platega\PlategaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Страница «Оплата» в кабинете: срок подписки + число устройств + срок доп. устройств → ссылка на оплату.
 */
class CabinetCreatePaymentLinkController extends Controller
{
    public function __invoke(Request $request, PlategaClient $platega): JsonResponse
    {
        if (! $platega->isConfigured()) {
            return response()->json(['error' => 'payments_not_configured'], 503);
        }

        $user = $request->user();

        $data = $request->validate([
            'term' => ['nullable', 'string', 'max:16'],
            'devices' => ['required', 'integer', 'min:0', 'max:100'],
            'device_months' => ['nullable', 'integer', 'min:0', 'max:24'],
        ]);

        $sub = AccountSubscription::forUser($user);

        try {
            $quote = AccountOrderFactory::quote(
                $sub,
                isset($data['term']) && $data['term'] !== '' ? (string) $data['term'] : null,
                (int) $data['devices'],
                (int) ($data['device_months'] ?? 0),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $order = AccountOrderFactory::create($quote, [
            'user_id' => $user->id,
            'subscription_id' => $sub?->id,
        ]);

        return $this->startPayment($platega, $order, $request, (string) $order->claim_token, $user);
    }

    /**
     * Создаёт транзакцию Platega под уже сохранённый заказ и отдаёт ссылку на оплату.
     * Способ оплаты не навязываем — Platega сама показывает выбор (СБП / карта).
     */
    private function startPayment(
        PlategaClient $platega,
        PaymentOrder $order,
        Request $request,
        string $claimToken,
        $user,
    ): JsonResponse {
        $tx = $platega->createTransaction(
            amountRub: (int) $order->amount_rub,
            description: (string) $order->description,
            returnUrl: $this->paymentDoneUrl($request, $claimToken),
            failedUrl: (string) config('platega.site_failed_url'),
            payload: (string) $order->order_id,
            paymentMethod: null,
            metadata: [
                'userId' => (string) $user->id,
                'userName' => 'web:'.$user->id,
            ],
        );

        $order->provider_transaction_id = (string) $tx['transactionId'];
        $order->status = 'pending';
        $order->provider_payload = $tx['raw'] ?? $tx;
        $order->save();

        return response()->json([
            'url' => $tx['url'],
        ]);
    }

    private function paymentDoneUrl(Request $request, string $claimToken): string
    {
        $request->session()->put('cabinet_payment_claim', $claimToken);

        return (string) config('platega.site_return_url');
    }
}

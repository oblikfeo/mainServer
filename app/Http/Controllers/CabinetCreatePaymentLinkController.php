<?php

namespace App\Http\Controllers;

use App\Models\PaymentOrder;
use App\Services\Wata\WataH2hClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

class CabinetCreatePaymentLinkController extends Controller
{
    public function __invoke(Request $request, WataH2hClient $wata): JsonResponse
    {
        if (trim((string) config('wata.access_token')) === '') {
            return response()->json(['error' => 'payments_not_configured'], 503);
        }

        $user = $request->user();

        $data = $request->validate([
            'plan' => ['required', 'string', 'max:32'],
            'period' => ['required', 'string', 'max:32'],
        ]);

        $plan = (string) $data['plan'];
        $period = (string) $data['period'];

        $products = config('payments.products', []);
        $planCfg = is_array($products) ? ($products[$plan] ?? null) : null;
        $rows = is_array($planCfg) ? ($planCfg['rows'] ?? null) : null;
        $row = is_array($rows) ? ($rows[$period] ?? null) : null;
        if (! is_array($row)) {
            return response()->json(['error' => 'unknown_tariff'], 422);
        }

        $devices = (int) ($planCfg['devices'] ?? 0);
        $days = (int) ($row['days'] ?? 0);
        $quotaGb = (int) ($row['quota_gb'] ?? 0);
        $amountRub = (int) ($row['amount_rub'] ?? 0);
        if ($devices < 1 || $days < 1 || $quotaGb < 1 || $amountRub < 1) {
            throw new RuntimeException('Неверная конфигурация payments.products для '.$plan.' / '.$period);
        }

        $orderId = 'ord_'.(string) Str::ulid();
        $desc = 'Подписка '.$plan.' · '.$period;

        $order = PaymentOrder::query()->create([
            'order_id' => $orderId,
            'user_id' => $user->id,
            'provider' => 'wata',
            'status' => 'created',
            'amount_rub' => $amountRub,
            'currency' => 'RUB',
            'description' => $desc,
            'tariff_plan' => $plan,
            'tariff_period' => $period,
            'days' => $days,
            'devices' => $devices,
            'quota_gb' => $quotaGb,
        ]);

        $payload = [
            'type' => 'OneTime',
            'amount' => (float) number_format($amountRub, 2, '.', ''),
            'currency' => 'RUB',
            'description' => $desc,
            'orderId' => $orderId,
            'successRedirectUrl' => (string) config('wata.success_url'),
            'failRedirectUrl' => (string) config('wata.fail_url'),
        ];

        $link = $wata->createPaymentLink($payload);

        $order->provider_link_id = $link['id'];
        $order->status = 'pending';
        $order->provider_payload = $link;
        $order->save();

        return response()->json([
            'url' => $link['url'],
        ]);
    }
}


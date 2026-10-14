<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentOrder;
use App\Models\User;
use App\Services\Account\AccountOrderFactory;
use App\Services\Account\AccountOverview;
use App\Services\Account\AccountPricing;
use App\Services\Account\AccountSubscription;
use App\Services\Platega\PlategaClient;
use App\Services\Wata\WataH2hClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

final class TelegramBotPaymentController extends Controller
{
    private const NOT_LINKED = [
        'ok' => false,
        'error' => 'not_linked',
        'message' => 'Telegram не привязан к аккаунту. Откройте Личный кабинет на сайте и привяжите Telegram.',
    ];

    public function catalog(): JsonResponse
    {
        $methods = config('platega.payment_methods', []);
        $terms = array_values(AccountPricing::terms());

        // Старый бот (до обновления) читает plans/periods — отдаём ему те же сроки одним «тарифом».
        $legacyPeriods = [];
        foreach ($terms as $i => $term) {
            $legacyPeriods[] = [
                'index' => $i,
                'period' => $term['label'],
                'amount_rub' => $term['amount_rub'],
                'days' => $term['days'],
            ];
        }

        return response()->json([
            'ok' => true,
            'terms' => $terms,
            'base_devices' => AccountPricing::baseDevices(),
            'max_devices' => AccountPricing::maxDevices(),
            'device_month_rub' => AccountPricing::deviceMonthRub(),
            'device_max_months' => AccountPricing::deviceMaxMonths(),
            'plans' => [[
                'plan' => 'solo',
                'label' => 'Подписка ('.AccountPricing::baseDevices().' устр.)',
                'devices' => AccountPricing::baseDevices(),
                'periods' => $legacyPeriods,
                'renew_periods' => $legacyPeriods,
            ]],
            'payment_methods' => [
                ['code' => 'sbp', 'label' => '📱 СБП', 'id' => (int) ($methods['sbp'] ?? 2)],
                ['code' => 'card', 'label' => '💳 Картой', 'id' => (int) ($methods['card'] ?? 11)],
            ],
        ]);
    }

    /** Состояние подписки аккаунта для экрана оплаты в боте. */
    public function subscriptions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
        ]);

        $user = $this->resolveUser((int) $data['telegram_user_id']);
        if ($user === null) {
            return response()->json(self::NOT_LINKED, 404);
        }

        $account = AccountOverview::forUser($user);
        $sub = $account['subscription'];
        $tz = (string) config('app.timezone');

        $slots = [];
        foreach ($account['slots'] as $slot) {
            if ($slot['kind'] !== 'extra') {
                continue;
            }
            $slots[] = [
                'active' => (bool) $slot['active'],
                'left_days' => (int) $slot['left_days'],
            ];
        }

        $items = [];
        if ($sub !== null) {
            $items[] = [
                'id' => (int) $sub->id,
                'plan' => 'solo',
                'public_code' => '#'.$sub->public_code,
                'active' => ! $sub->isExpired(),
                'created' => $sub->created_at?->timezone($tz)->format('d.m.Y'),
                'devices' => (int) $account['device_limit'],
            ];
        }

        return response()->json([
            'ok' => true,
            'account' => [
                'state' => $account['state'],
                'expires' => $account['expires_at']?->format('d.m.Y'),
                'days_left' => $account['days_left'],
                'unlimited_devices' => (bool) $account['unlimited_devices'],
                'base_devices' => (int) $account['base_devices'],
                'extra_devices' => (int) $account['extra_total'],
                'paid_extra_devices' => AccountOrderFactory::paidSlots($sub),
                'over_base_devices' => AccountPricing::overBaseDevices($sub),
                'max_devices' => (int) $account['base_devices'] + (int) $account['max_extra'],
                'extra_slots' => $slots,
            ],
            'items' => $items,
        ]);
    }

    public function create(Request $request, PlategaClient $platega, WataH2hClient $wata): JsonResponse
    {
        $provider = self::provider();
        $configured = $provider === 'wata'
            ? trim((string) config('wata.access_token')) !== ''
            : $platega->isConfigured();
        if (! $configured) {
            return response()->json([
                'ok' => false,
                'error' => 'payments_not_configured',
                'message' => 'Оплата в боте временно недоступна.',
            ], 503);
        }

        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
            'telegram_username' => ['nullable', 'string', 'max:64'],
            'term' => ['nullable', 'string', 'max:16'],
            'devices' => ['nullable', 'integer', 'min:0', 'max:100'],
            'device_months' => ['nullable', 'integer', 'min:0', 'max:24'],
            'payment_method' => ['required', 'string', 'in:sbp,card'],
            // Поля старого бота: тариф и номер срока.
            'plan' => ['nullable', 'string', 'in:solo,family'],
            'period_index' => ['nullable', 'integer', 'min:0', 'max:20'],
        ]);

        $user = $this->resolveUser((int) $data['telegram_user_id']);
        if ($user === null) {
            return response()->json(self::NOT_LINKED, 404);
        }

        $paymentMethodCode = (string) $data['payment_method'];
        $sub = AccountSubscription::forUser($user);

        $termKey = trim((string) ($data['term'] ?? ''));
        $totalDevices = (int) ($data['devices'] ?? 0);
        $deviceMonths = (int) ($data['device_months'] ?? 0);

        if (isset($data['plan'])) {
            if ((string) $data['plan'] !== 'solo') {
                return response()->json([
                    'ok' => false,
                    'error' => 'menu_outdated',
                    'message' => 'Меню оплаты обновилось. Нажмите «Оплатить» ещё раз.',
                ], 422);
            }
            $termKey = (string) (array_keys(AccountPricing::terms())[(int) ($data['period_index'] ?? 0)] ?? '');
            $totalDevices = 0;
            $deviceMonths = 0;
        }
        if ($termKey === 'none') {
            $termKey = '';
        }

        try {
            $quote = AccountOrderFactory::quote($sub, $termKey !== '' ? $termKey : null, $totalDevices, $deviceMonths);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
                'message' => self::quoteErrorMessage($e->getMessage()),
            ], 422);
        }

        try {
            $order = AccountOrderFactory::create($quote, [
                'claim_token' => null,
                'user_id' => $user->id,
                'provider' => $provider,
            ]);

            if ($provider === 'wata') {
                return $this->startWataPayment($wata, $order, $paymentMethodCode);
            }

            $methodId = $this->paymentMethodId($paymentMethodCode);
            $telegramUserId = (int) $data['telegram_user_id'];
            $username = trim((string) ($data['telegram_username'] ?? ''));
            $userName = $username !== '' ? '@'.ltrim($username, '@') : 'tg:'.$telegramUserId;

            $tx = $platega->createTransaction(
                amountRub: (int) $order->amount_rub,
                description: (string) $order->description,
                returnUrl: (string) config('platega.return_url'),
                failedUrl: (string) config('platega.failed_url'),
                payload: (string) $order->order_id,
                paymentMethod: $methodId,
                metadata: [
                    'userId' => (string) $telegramUserId,
                    'userName' => $userName,
                ],
            );

            $order->provider_transaction_id = $tx['transactionId'];
            $order->status = 'pending';
            $order->provider_payload = $tx['raw'] ?? $tx;
            $order->save();

            return response()->json([
                'ok' => true,
                'order_id' => $order->order_id,
                'pay_url' => $tx['url'],
                'amount_rub' => (int) $order->amount_rub,
                'description' => (string) $order->description,
                'payment_method' => $paymentMethodCode,
                'expires_in' => $tx['expiresIn'],
            ]);
        } catch (RuntimeException $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => 'payment_create_failed',
                'message' => 'Не удалось создать платёж. Попробуйте другой способ или позже.',
            ], 502);
        }
    }

    public function status(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telegram_user_id' => ['required', 'integer'],
            'order_id' => ['required', 'string', 'max:80'],
        ]);

        $user = $this->resolveUser((int) $data['telegram_user_id']);
        if ($user === null) {
            return response()->json(self::NOT_LINKED, 404);
        }

        $order = PaymentOrder::query()
            ->where('order_id', (string) $data['order_id'])
            ->where('user_id', $user->id)
            ->whereIn('provider', ['platega', 'wata'])
            ->first();

        if ($order === null) {
            return response()->json([
                'ok' => false,
                'error' => 'not_found',
                'message' => 'Заказ не найден.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'status' => (string) $order->status,
            'paid' => $order->status === 'paid',
        ]);
    }

    /** Платёжка для оплат из бота: wata (по умолчанию) или platega. */
    private static function provider(): string
    {
        return config('payments.telegram_bot_provider') === 'platega' ? 'platega' : 'wata';
    }

    /**
     * Wata сама показывает на своей странице выбор СБП/карта, поэтому способ из бота
     * в платёж не передаём — только возвращаем боту для подписи кнопки.
     */
    private function startWataPayment(WataH2hClient $wata, PaymentOrder $order, string $paymentMethodCode): JsonResponse
    {
        $link = $wata->createPaymentLink([
            'type' => 'OneTime',
            'amount' => (float) number_format((int) $order->amount_rub, 2, '.', ''),
            'currency' => 'RUB',
            'description' => (string) $order->description,
            'orderId' => (string) $order->order_id,
            'successRedirectUrl' => (string) config('platega.return_url'),
            'failRedirectUrl' => (string) config('platega.failed_url'),
        ]);

        $order->provider_link_id = $link['id'];
        $order->status = 'pending';
        $order->provider_payload = $link;
        $order->save();

        return response()->json([
            'ok' => true,
            'order_id' => $order->order_id,
            'pay_url' => $link['url'],
            'amount_rub' => (int) $order->amount_rub,
            'description' => (string) $order->description,
            'payment_method' => $paymentMethodCode,
            'expires_in' => null,
        ]);
    }

    private function resolveUser(int $telegramUserId): ?User
    {
        return User::query()->where('telegram_id', $telegramUserId)->first();
    }

    private static function quoteErrorMessage(string $code): string
    {
        return match ($code) {
            'term_required' => 'Подписка не активна — выберите срок подписки.',
            'devices_out_of_range' => 'Столько устройств подключить нельзя.',
            'device_months_required', 'device_months_out_of_range' => 'Выберите, на сколько месяцев оплатить доп. устройства.',
            'nothing_to_pay' => 'Ничего не выбрано для оплаты.',
            default => 'Не удалось рассчитать оплату. Откройте меню оплаты заново.',
        };
    }

    private function paymentMethodId(string $code): int
    {
        $methods = config('platega.payment_methods', []);
        $id = (int) ($methods[$code] ?? 0);
        if ($id < 1) {
            throw new RuntimeException('unknown_payment_method');
        }

        return $id;
    }
}

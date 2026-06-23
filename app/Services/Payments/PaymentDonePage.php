<?php

namespace App\Services\Payments;

use App\Models\PaymentOrder;
use App\Models\User;
use App\Services\Referral\ReferralLinkBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PaymentDonePage
{
    public function __construct(
        private readonly ReferralLinkBuilder $referralLinks,
    ) {}

    public function resolveOrder(Request $request, User $user): ?PaymentOrder
    {
        $claim = $request->session()->get('cabinet_payment_claim');
        if (is_string($claim) && $claim !== '') {
            $order = PaymentOrder::query()
                ->where('claim_token', $claim)
                ->where('user_id', $user->id)
                ->with(['user', 'subscription'])
                ->first();
            if ($order !== null) {
                return $order;
            }
        }

        $order = PaymentOrder::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'paid'])
            ->where('created_at', '>=', now()->subHours(48))
            ->orderByDesc('id')
            ->with(['user', 'subscription'])
            ->first();

        if ($order === null) {
            return null;
        }

        if (! is_string($order->claim_token) || $order->claim_token === '') {
            $order->claim_token = Str::random(48);
            $order->save();
        }

        return $order;
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(PaymentOrder $order, ?string $plainPassword = null): array
    {
        /** @var User|null $buyer */
        $buyer = $order->user;
        $subscription = $order->subscription;
        $claimToken = (string) ($order->claim_token ?? '');

        $referralLink = null;
        if ($buyer !== null) {
            $referralLink = $this->referralLinks->forUser($buyer);
        }

        return [
            'order' => $order,
            'buyer' => $buyer,
            'subscription' => $subscription,
            'plainPassword' => $plainPassword,
            'cabinetLoginUrl' => $subscription !== null
                ? route('auth.via_token', ['token' => $subscription->token], absolute: false)
                : null,
            'claimToken' => $claimToken,
            'shouldPoll' => $order->status !== 'paid' || ($order->status === 'paid' && $subscription === null),
            'referralLink' => $referralLink,
        ];
    }
}

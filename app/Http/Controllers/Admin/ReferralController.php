<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Referral\ReferralLinkBuilder;
use App\Services\Referral\ReferralMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ReferralController extends Controller
{
    public function index(Request $request, ReferralMetrics $metrics, ReferralLinkBuilder $referralLinks): View
    {
        $search = trim((string) $request->query('q', ''));
        $referrerFilter = trim((string) $request->query('referrer', ''));

        $stats = [
            'referrers' => (int) User::query()->whereHas('referrals')->count(),
            'referred' => (int) User::query()->whereNotNull('referred_by')->count(),
            'paid' => (int) User::query()->whereNotNull('referred_by')->whereHas('purchases')->count(),
        ];

        $referrersQ = User::query()
            ->whereHas('referrals')
            ->withCount([
                'referrals',
                'referrals as referrals_paid_count' => fn ($q) => $q->whereHas('purchases'),
            ])
            ->orderByDesc('referrals_count');

        if ($search !== '') {
            $like = '%'.$search.'%';
            $referrersQ->where(function ($q) use ($like) {
                $q->where('email', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('referral_code', 'like', $like);
            });
        }

        $referrers = $referrersQ->paginate(20, pageName: 'referrers_page')->withQueryString();

        foreach ($referrers as $referrer) {
            /** @var User $referrer */
            $referrer->setAttribute('referrals_active_count', $metrics->countReferralsWithActiveSubscription((int) $referrer->id));
            $referrer->setAttribute('referral_url', $referralLinks->forUser($referrer));
        }

        $recentQ = User::query()
            ->whereNotNull('referred_by')
            ->with(['referrer:id,name,email,referral_code'])
            ->orderByDesc('id');

        if ($referrerFilter !== '') {
            $recentQ->whereHas('referrer', function ($q) use ($referrerFilter) {
                $like = '%'.$referrerFilter.'%';
                $q->where('email', 'like', $like)->orWhere('name', 'like', $like);
            });
        } elseif ($search !== '') {
            $like = '%'.$search.'%';
            $recentQ->where(function ($q) use ($like) {
                $q->where('email', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhereHas('referrer', fn ($r) => $r->where('email', 'like', $like)->orWhere('name', 'like', $like));
            });
        }

        $recentReferrals = $recentQ->paginate(30, pageName: 'recent_page')->withQueryString();

        $nowMs = $metrics->nowMs();
        foreach ($recentReferrals as $referee) {
            /** @var User $referee */
            $hasPurchase = $referee->purchases()->exists();
            $activeSub = $referee->subscriptions()
                ->where(function ($q) use ($nowMs) {
                    $q->where('expiry_ms', '<=', 0)
                        ->orWhere('expiry_ms', '>', $nowMs);
                })
                ->exists();

            if ($activeSub) {
                $status = 'Активная подписка';
                $statusKind = 'ok';
            } elseif ($hasPurchase) {
                $status = 'Оплатил';
                $statusKind = 'paid';
            } else {
                $status = 'Только регистрация';
                $statusKind = 'wait';
            }

            $referee->setAttribute('ref_status', $status);
            $referee->setAttribute('ref_status_kind', $statusKind);
        }

        $partners = [];
        foreach ((array) config('referral.partners', []) as $key => $cfg) {
            if (! is_array($cfg)) {
                continue;
            }
            $email = strtolower(trim((string) ($cfg['referrer_email'] ?? '')));
            if ($email === '') {
                continue;
            }
            $partnerUser = User::query()->where('email', $email)->first();
            $partners[] = [
                'key' => (string) $key,
                'display_name' => (string) ($cfg['display_name'] ?? $key),
                'route' => (string) ($cfg['route'] ?? ''),
                'email' => $email,
                'user' => $partnerUser,
                'registered' => $partnerUser !== null ? (int) $partnerUser->referrals()->count() : 0,
                'paid' => $partnerUser !== null ? $metrics->countReferralsWithAnyPurchase((int) $partnerUser->id) : 0,
                'active' => $partnerUser !== null ? $metrics->countReferralsWithActiveSubscription((int) $partnerUser->id) : 0,
                'referral_url' => $partnerUser !== null ? $referralLinks->forUser($partnerUser) : null,
            ];
        }

        return view('admin.referral', [
            'stats' => $stats,
            'referrers' => $referrers,
            'recentReferrals' => $recentReferrals,
            'partners' => $partners,
            'search' => $search,
            'referrerFilter' => $referrerFilter,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Referral\ReferralMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ReferralController extends Controller
{
    public function index(Request $request, ReferralMetrics $metrics): View
    {
        $search = trim((string) $request->query('q', ''));
        $referrerFilter = trim((string) $request->query('referrer', ''));

        $topReferrers = User::query()
            ->whereHas('referrals')
            ->withCount([
                'referrals',
                'referrals as referrals_paid_count' => fn ($q) => $q->whereHas('purchases'),
            ])
            ->orderByDesc('referrals_count')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $recentQ = User::query()
            ->whereNotNull('referred_by')
            ->with(['referrer:id,name,email'])
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

        $recentReferrals = $recentQ->paginate(30)->withQueryString();

        foreach ($recentReferrals as $referee) {
            /** @var User $referee */
            $paid = $referee->purchases()->exists();
            $referee->setAttribute('ref_status', $paid ? 'Оплатил' : 'Зарегистрировался');
            $referee->setAttribute('ref_status_kind', $paid ? 'paid' : 'registered');
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
            ];
        }

        return view('admin.referral', [
            'topReferrers' => $topReferrers,
            'recentReferrals' => $recentReferrals,
            'partners' => $partners,
            'search' => $search,
            'referrerFilter' => $referrerFilter,
        ]);
    }
}

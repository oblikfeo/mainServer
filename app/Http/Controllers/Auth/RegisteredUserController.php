<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Referral\ReferralRewardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class RegisteredUserController extends Controller
{
    public function create(Request $request): View
    {
        $rawRef = $request->query('ref');
        $hasRefInUrl = is_string($rawRef) && trim($rawRef) !== '';
        if (! $hasRefInUrl) {
            $request->session()->forget('pending_referral_code');

            return view('auth.register', [
                'invitedBy' => null,
                'partnerLabel' => null,
            ]);
        }

        $invitedBy = null;
        $pendingRef = $request->session()->get('pending_referral_code');
        if (is_string($pendingRef) && $pendingRef !== '') {
            $invitedBy = User::query()
                ->select(['id', 'name', 'email'])
                ->where('referral_code', $pendingRef)
                ->first();
        }

        return view('auth.register', [
            'invitedBy' => $invitedBy,
            'partnerLabel' => null,
        ]);
    }

    public function createPartnerReset(Request $request): View
    {
        $referrer = $this->partnerReferrer('reset');
        if ($referrer === null) {
            throw new ServiceUnavailableHttpException(null, 'Партнёрская регистрация временно недоступна.');
        }

        $request->session()->put('pending_referral_code', (string) $referrer->referral_code);
        $request->session()->put('referral_partner_key', 'reset');

        $displayName = (string) config('referral.partners.reset.display_name', 'Reset');

        return view('auth.register', [
            'invitedBy' => null,
            'partnerLabel' => $displayName,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, ReferralRewardService $referralRewards): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Rules\Password::min(8)],
            'offer_accepted' => ['accepted'],
        ], [
            'offer_accepted.accepted' => 'Нужно согласие с публичной офертой.',
        ]);

        $email = (string) $request->email;
        $local = Str::before($email, '@');
        $name = $local !== '' ? $local : 'user';

        $referredById = null;
        $pendingRef = $request->session()->pull('pending_referral_code');
        if (is_string($pendingRef) && $pendingRef !== '') {
            $referrer = User::query()->where('referral_code', $pendingRef)->first();
            if ($referrer !== null) {
                $referredById = $referrer->id;
            }
        }
        $request->session()->forget('referral_partner_key');

        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($request->password),
        ]);
        if ($referredById !== null) {
            $user->referred_by = $referredById;
        }
        $user->save();

        $referralRewards->onReferredUserRegistered($user);

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    private function partnerReferrer(string $partnerKey): ?User
    {
        $cfg = config('referral.partners.'.$partnerKey);
        if (! is_array($cfg)) {
            return null;
        }

        $email = strtolower(trim((string) ($cfg['referrer_email'] ?? '')));
        if ($email === '') {
            return null;
        }

        return User::query()
            ->select(['id', 'name', 'email', 'referral_code'])
            ->where('email', $email)
            ->first();
    }
}

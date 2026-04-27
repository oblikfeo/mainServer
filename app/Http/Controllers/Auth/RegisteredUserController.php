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
}

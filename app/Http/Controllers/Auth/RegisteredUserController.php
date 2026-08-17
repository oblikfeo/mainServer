<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Promo\PromoCodeService;
use App\Services\Referral\ReferralRewardService;
use App\Services\Xui\XuiPanelException;
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

    public function createPromo(): View
    {
        return view('auth.register', [
            'invitedBy' => null,
            'partnerLabel' => null,
            'showPromoCode' => true,
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

        $partnerCfg = config('referral.partners.reset', []);
        $displayName = is_array($partnerCfg) ? (string) ($partnerCfg['display_name'] ?? 'Reset') : 'Reset';
        $partnerLogo = is_array($partnerCfg) ? (string) ($partnerCfg['logo'] ?? '') : '';

        return view('auth.register', [
            'invitedBy' => null,
            'partnerLabel' => $displayName,
            'partnerLogo' => $partnerLogo,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, ReferralRewardService $referralRewards): RedirectResponse
    {
        return $this->registerUser($request, $referralRewards, null);
    }

    /**
     * @throws ValidationException
     */
    public function storePromo(
        Request $request,
        ReferralRewardService $referralRewards,
        PromoCodeService $promo,
    ): RedirectResponse {
        return $this->registerUser($request, $referralRewards, $promo);
    }

    /**
     * @throws ValidationException
     */
    private function registerUser(
        Request $request,
        ReferralRewardService $referralRewards,
        ?PromoCodeService $promo,
    ): RedirectResponse {
        $rules = [
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', Rules\Password::min(8)],
            'offer_accepted' => ['accepted'],
        ];
        if ($promo !== null) {
            $rules['promo_code'] = ['nullable', 'string', 'max:32'];
        }

        $request->validate($rules, [
            'offer_accepted.accepted' => 'Нужно согласие с публичной офертой.',
        ]);

        $promoCode = '';
        if ($promo !== null) {
            $promoCode = $promo->normalize($request->input('promo_code'));
            if ($promoCode !== '' && $promo->definition($promoCode) === null) {
                throw ValidationException::withMessages([
                    'promo_code' => 'Промокод не найден.',
                ]);
            }
        }

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

        if ($promo !== null && $promoCode !== '') {
            try {
                $promo->redeemOnRegistration($user, $promoCode);
            } catch (XuiPanelException $e) {
                report($e);
                $user->delete();

                throw ValidationException::withMessages([
                    'promo_code' => 'Не удалось активировать промокод. Попробуйте ещё раз.',
                ]);
            } catch (\Throwable $e) {
                report($e);
                $user->delete();

                throw ValidationException::withMessages([
                    'promo_code' => 'Не удалось активировать промокод. Попробуйте ещё раз.',
                ]);
            }
        }

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

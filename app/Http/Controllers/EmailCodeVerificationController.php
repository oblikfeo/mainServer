<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerificationCodeMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailCodeVerificationController extends Controller
{
    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'email-code-already-verified');
        }

        $sentAt = $user->email_verification_code_sent_at;
        if ($sentAt !== null && now()->diffInSeconds($sentAt) < 3600) {
            return back()->withErrors([
                'email_code' => 'Код уже отправлен. Повторная отправка доступна раз в час.',
            ]);
        }

        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $user->forceFill([
            'email_verification_code_hash' => hash_hmac('sha256', $code, (string) config('app.key')),
            'email_verification_code_sent_at' => now(),
        ])->save();

        $brand = (string) config('marketing.brand_name', config('app.name', 'Надежда'));
        $fromAddress = (string) (config('marketing.support_email') ?: config('mail.from.address', 'support@nadezhda.space'));
        $fromName = (string) ($brand.' · поддержка');

        Mail::to($user->email)->send(new EmailVerificationCodeMail(
            code: $code,
            brand: $brand,
            supportFromAddress: $fromAddress,
            supportFromName: $fromName,
        ));

        return back()->with('status', 'email-code-sent');
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'email-code-already-verified');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{4}$/'],
        ], [
            'code.required' => 'Введите код из письма.',
            'code.regex' => 'Код должен состоять из 4 цифр.',
        ]);

        $code = (string) $validated['code'];
        $expectedHash = (string) ($user->email_verification_code_hash ?? '');
        if ($expectedHash === '') {
            return back()->withErrors([
                'code' => 'Сначала отправьте код на почту.',
            ]);
        }

        $actualHash = hash_hmac('sha256', $code, (string) config('app.key'));
        if (! hash_equals($expectedHash, $actualHash)) {
            return back()->withErrors([
                'code' => 'Неверный код.',
            ]);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code_hash' => null,
        ])->save();

        return back()->with('status', 'email-code-verified');
    }
}


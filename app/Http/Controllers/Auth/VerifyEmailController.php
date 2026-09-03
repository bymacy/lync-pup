<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * Only while still unverified does the extra "token" query param need
     * to match the user's current email_verification_token — that's what
     * makes an old, superseded verification link stop working the moment a
     * newer one is sent (see User::sendEmailVerificationNotification() and
     * VerifyEmailNotification). Once verified, any lingering old link is
     * just a harmless no-op redirect, same as Laravel's stock behavior.
     *
     * Unlike Breeze's stock behavior (redirect straight into the
     * dashboard), self-registered Founder accounts still need admin
     * approval after verifying — so this logs them out and sends them back
     * to login with that context in a flash message, instead.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            if ($request->query('token') !== $request->user()->email_verification_token) {
                abort(403, 'This verification link has been replaced by a newer one. Please check your email for the latest link.');
            }

            if ($request->user()->markEmailAsVerified()) {
                event(new Verified($request->user()));
            }

            $request->user()->forceFill(['email_verification_token' => null])->save();
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'Your email has been verified! Your account is now pending admin approval — you\'ll be notified once it\'s reviewed.');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        // Same stale-tab scenario as EmailVerificationPromptController: this
        // fires when someone clicks "Resend" on a waiting tab whose account
        // was already verified in another tab. route('dashboard') here used
        // to 403 a self-registered Founder outright — send them to log in
        // instead, since verification's job is already done.
        if ($request->user()->hasVerifiedEmail()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Your email is already verified. Please log in.');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}

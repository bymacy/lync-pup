<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        // A stale "waiting for verification" tab reloading (or its polling
        // — see verify-email.blade.php — noticing the account is already
        // verified) used to fall through to route('dashboard'), which is
        // Admin-only — a self-registered Founder (role Startup) hitting
        // this branch got a raw 403 instead of ever reaching login. Email
        // verification's own job is done at this point; send them to log
        // in properly rather than guessing which dashboard they're allowed
        // into, same destination VerifyEmailController already uses right
        // after the link itself is clicked.
        if ($request->user()->hasVerifiedEmail()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Your email is already verified. Please log in.');
        }

        return view('auth.verify-email');
    }
}

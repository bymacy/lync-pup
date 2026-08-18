<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Email verification is a step in the self-registered Founder flow
        // only — Admin accounts are created directly (e.g. via seeder) and
        // never go through it, so this must never apply to them regardless
        // of what their email_verified_at column holds. An unverified
        // Startup account is let through LoginRequest specifically so it
        // can get back here — send it to the verify-email prompt rather
        // than a dashboard it isn't cleared to use yet.
        //
        // Uses intended() rather than a hardcoded redirect: if this login
        // was itself triggered by clicking the signed verification link
        // while logged out (e.g. opened on a different device/browser than
        // where they registered), Laravel already remembers that signed
        // URL as the "intended" destination. Redirecting straight to
        // verification.notice unconditionally would silently discard that
        // and the click would never actually complete verification —
        // leaving the founder stuck re-clicking a link that never re-runs.
        if ($request->user()->role === 'Startup' && ! $request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('verification.notice', absolute: false));
        }

        $redirectRoute = $request->user()->role === 'Startup' ? 'startup.dashboard' : 'dashboard';

        return redirect()->intended(route($redirectRoute, absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
    Auth::guard('web')->logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();

    return redirect()->route('login');
    }
}

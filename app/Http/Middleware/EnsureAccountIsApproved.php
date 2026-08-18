<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsApproved
{
    /**
     * Blocks a self-registered Founder from using the app while their
     * account is still Pending (or was Rejected/deactivated). Without this,
     * someone could bypass the login-form gate in
     * LoginRequest::authenticate() simply by navigating straight to a
     * dashboard URL while their post-registration session — created by the
     * auto-login in RegisteredUserController — is still active, before an
     * admin has approved them.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isApprovedAccount()) {
            $message = match ($user->account_status) {
                'Pending' => "Your account is still awaiting admin approval. We'll notify you once it's approved.",
                'Rejected' => 'Your registration was not approved. Please contact PUP TBIDO for more information.',
                default => 'Your account is currently inactive. Please contact PUP TBIDO for assistance.',
            };

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', $message);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'role' => ['required', 'string', 'in:Admin,Startup'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Please select whether you are signing in as a Founder or an Admin.',
            'role.in' => 'Invalid sign-in type selected.',
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }


        // Enforce that the selected tab matches the account's actual role
        if (Auth::user()->role !== $this->input('role')) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            $selected = $this->input('role') === 'Startup' ? 'Founder' : 'Admin';

            throw ValidationException::withMessages([
                'email' => "This account is not registered as {$selected}. Please select the correct sign-in type.",
            ]);
        }

        // Self-registered Founder accounts start "Pending" and can't sign in
        // until an admin approves them. Block anything that isn't "Active" —
        // but only once their email is actually verified. Otherwise, someone
        // whose post-registration session dropped before they clicked the
        // verification link would have no way back in at all: logging in
        // normally would just get blocked here, with no path to a verified,
        // approved account. Skipping the check while unverified lets them
        // back in specifically to (re)verify — AuthenticatedSessionController
        // routes unverified users to the verify-email prompt regardless of
        // approval status, and the "approved" middleware still blocks a
        // Pending account from reaching any real Startup route either way.
        //
        // Scoped to role === 'Startup' only — this approval flow only ever
        // applies to self-registered Founder accounts. Admins are created
        // directly (e.g. via seeder), never go through this pending/approved
        // lifecycle, and must never be blocked by it regardless of whatever
        // value their account_status column happens to hold.
        if (Auth::user()->role === 'Startup' && Auth::user()->hasVerifiedEmail() && ! Auth::user()->isApprovedAccount()) {
            $message = match (Auth::user()->account_status) {
                'Pending' => 'Your account is still awaiting admin approval. We\'ll notify you once it\'s approved.',
                'Rejected' => 'Your registration was not approved. Please contact PUP TBIDO for more information.',
                default => 'Your account is currently inactive. Please contact PUP TBIDO for assistance.',
            };

            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => $message,
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
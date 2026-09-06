<?php

namespace App\Providers;

use App\Notifications\NewRoadblockSubmitted;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('admin-only', fn ($user) => $user->role === 'Admin');
        Gate::define('startup-only', fn ($user) => $user->role === 'Startup');

        // Branded verification email for the self-service Founder
        // registration flow, replacing Laravel's default plain-text one.
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verify Your Email - LYNC PUP')
                ->view('emails.verify-email', ['url' => $url]);
        });

        // Branded "forgot password" email, same reasoning as above.
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Reset Your Password - LYNC PUP')
                ->view('emails.reset-password', ['url' => $url]);
        });

        // Admin sidebar "new item" red-dot badges (see
        // components/layouts/admin.blade.php's $navItems 'hasUnseen' key).
        // Shared globally rather than per-controller so every admin page —
        // not just the two that actually clear it — shows the current
        // state. Keyed by route name so the nav loop can look each one up
        // directly; a module with nothing to flag simply won't have a key,
        // same as `!empty(...)` already treats a missing 'hasUnseen'.
        View::composer('components.layouts.admin', function ($view) {
            $user = auth()->user();

            $badges = [];

            if ($user && $user->isAdmin()) {
                $badges['admin.roadblocks.index'] = $user->unreadNotifications()
                    ->where('type', NewRoadblockSubmitted::class)
                    ->exists();

                $currentSignature = Cache::get('risk_monitoring_signature');
                $badges['admin.risk-monitoring.index'] = $currentSignature !== null
                    && $currentSignature !== $user->risk_monitoring_seen_signature;
            }

            $view->with('adminSidebarBadges', $badges);
        });
    }
}
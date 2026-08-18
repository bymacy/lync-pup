<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
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
    }
}
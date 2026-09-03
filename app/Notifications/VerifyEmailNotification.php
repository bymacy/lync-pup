<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Same as Laravel's stock VerifyEmail notification, except the signed URL
 * also carries a "token" query param tied to the user's current
 * email_verification_token column (see User::sendEmailVerificationNotification()
 * and VerifyEmailController). That's what makes only the most recently sent
 * verification link valid — Laravel's default signed URL has no such
 * concept and would let every previously issued link keep working right up
 * until its own expiration.
 */
class VerifyEmailNotification extends BaseVerifyEmail
{
    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes((int) config('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
                'token' => $notifiable->email_verification_token,
            ]
        );
    }
}

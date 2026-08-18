<?php

namespace Tests\Feature\Auth;

use App\Models\Startup;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        // Unlike Breeze's default (straight into the dashboard), a
        // self-registered Founder still needs admin approval after
        // verifying, so VerifyEmailController logs them out and sends them
        // to the "pending review" screen instead.
        $this->assertGuest();
        $response->assertRedirect(route('registration.complete', absolute: false));
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    /**
     * "Change email address" on the verify-email screen is for a founder
     * who mistyped their email during registration. It deletes the
     * wrongly-emailed account entirely (so it doesn't linger as a stray
     * duplicate in the admin's Founder Application list), logs them out,
     * and sends them back to the registration form to start over — not to
     * the login screen, which they couldn't sign into under the mistyped
     * email anyway.
     */
    public function test_change_email_deletes_the_pending_account_and_redirects_to_register(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => 'Startup',
            'account_status' => 'Pending',
        ]);
        $startup = Startup::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('verification.change-email'));

        $this->assertGuest();
        $response->assertRedirect(route('register', absolute: false));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('startups', ['startup_id' => $startup->startup_id]);
    }

    /**
     * Safety guard on that same action: it must never delete an already
     * verified account, even if this route were somehow hit directly.
     */
    public function test_change_email_does_not_delete_an_already_verified_account(): void
    {
        $user = User::factory()->create([
            'role' => 'Startup',
            'account_status' => 'Pending',
        ]);

        $this->actingAs($user)->post(route('verification.change-email'));

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /**
     * Regression test: clicking the signed verification link while logged
     * out (e.g. on a different device/browser than where they registered)
     * must funnel through login and land back on that same signed link —
     * completing verification — rather than getting stranded on the
     * generic verify-email prompt with the click never actually applied.
     */
    public function test_visiting_the_verification_link_while_logged_out_completes_verification_after_login(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => 'Startup',
            'account_status' => 'Pending',
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Not authenticated yet — hitting the signed link redirects to
        // login and Laravel remembers it as the intended destination.
        $this->get($verificationUrl)->assertRedirect(route('login', absolute: false));

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'role' => 'Startup',
        ]);

        $response->assertRedirect($verificationUrl);

        $this->get($verificationUrl);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
    public function test_users_can_authenticate_when_role_matches(): void
    {
    $user = User::factory()->create([
        'role' => 'Startup',
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'role' => 'Startup',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_cannot_authenticate_when_role_does_not_match(): void
    {
    $user = User::factory()->create([
        'role' => 'Startup',
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'role' => 'Admin',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
    }

    public function test_login_requires_a_role_selection(): void
    {
    $user = User::factory()->create([
        'role' => 'Startup',
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        // No role field
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('role');
    }

    public function test_pending_account_cannot_sign_in(): void
    {
        $user = User::factory()->create([
            'role' => 'Startup',
            'account_status' => 'Pending',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'role' => 'Startup',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_rejected_account_cannot_sign_in(): void
    {
        $user = User::factory()->create([
            'role' => 'Startup',
            'account_status' => 'Rejected',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'role' => 'Startup',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * A Pending account whose session dropped before they clicked their
     * verification link must still be able to log back in — otherwise
     * they'd have no way to ever reach the verify-email screen again.
     */
    public function test_unverified_pending_account_can_sign_in_to_reverify(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => 'Startup',
            'account_status' => 'Pending',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'role' => 'Startup',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));
    }

    /**
     * Once verified, a Pending account goes back to being blocked at login
     * — verification alone doesn't grant access, admin approval still does.
     */
    public function test_verified_but_still_pending_account_cannot_sign_in(): void
    {
        $user = User::factory()->create([
            'role' => 'Startup',
            'account_status' => 'Pending',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'role' => 'Startup',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * The Pending/Rejected approval gate only applies to self-registered
     * Founder accounts. An Admin must never be blocked at login by it,
     * regardless of whatever value their account_status column happens to
     * hold (Admins are created directly, e.g. via seeder, and never go
     * through the founder application/approval flow at all).
     */
    public function test_admin_can_sign_in_even_if_account_status_is_not_active(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
            'account_status' => 'Pending',
        ]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
            'role' => 'Admin',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * Same as above, but for the email-verification gate: an Admin with a
     * null email_verified_at (the norm for seeded admin accounts, which
     * never go through the Founder verify-email step) must still land on
     * the real dashboard, not get bounced to the verify-email prompt.
     */
    public function test_admin_can_sign_in_even_if_email_is_unverified(): void
    {
        $admin = User::factory()->unverified()->create([
            'role' => 'Admin',
            'account_status' => 'Active',
        ]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
            'role' => 'Admin',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        // And the route itself must be reachable afterwards too — not just
        // the initial post-login redirect target.
        $this->get(route('dashboard'))->assertStatus(200);
    }
}

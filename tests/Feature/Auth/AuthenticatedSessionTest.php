<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_mass_assigning_email_verified_at_on_a_user_actually_persists_it(): void
    {
        // Regression guard for a silent mass-assignment bug: email_verified_at
        // was missing from User::$fillable, so seeders "verifying" an account
        // via update(['email_verified_at' => now()]) silently no-op'd — the
        // column stayed null in the database despite the seeder's intent.
        $user = User::create([
            'name' => 'Seeded Founder',
            'email' => 'seeded-founder@example.com',
            'password' => 'password',
            'role' => 'Startup',
            'account_status' => 'Active',
        ]);

        $user->update(['email_verified_at' => now()]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_a_startup_account_verified_via_mass_assignment_is_not_sent_to_the_verification_prompt_on_login(): void
    {
        // Mirrors exactly how DevDataSeeder "verifies" its seeded founder
        // account: create() first, then a separate update() call — not the
        // factory's Model::unguarded() path, so this is the scenario that
        // silently failed before email_verified_at was fillable.
        $user = User::factory()->create([
            'role' => 'Startup',
            'account_status' => 'Active',
            'password' => bcrypt('password'),
        ]);
        $user->update(['email_verified_at' => now()]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
            'role' => 'Startup',
        ]);

        $response->assertRedirect(route('startup.dashboard'));
    }

    public function test_a_startup_account_that_is_genuinely_unverified_is_sent_to_the_verification_prompt_on_login(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => 'Startup',
            'account_status' => 'Active',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
            'role' => 'Startup',
        ]);

        $response->assertRedirect(route('verification.notice'));
    }
}

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
    }

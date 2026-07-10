<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_protected_routes(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        Route::middleware(['auth', 'role:Admin'])
            ->get('/test-admin-route', fn () => 'ok');

        $response = $this->actingAs($admin)
            ->get('/test-admin-route');

        $response->assertOk();
    }

    public function test_startup_user_cannot_access_admin_routes(): void
    {
        $startup = User::factory()->create([
            'role' => 'Startup',
        ]);

        Route::middleware(['auth', 'role:Admin'])
            ->get('/test-admin-route', fn () => 'ok');

        $response = $this->actingAs($startup)
            ->get('/test-admin-route');

        $response->assertForbidden();
    }

    public function test_startup_can_access_startup_routes(): void
    {
        $startup = User::factory()->create([
            'role' => 'Startup',
        ]);

        Route::middleware(['auth', 'role:Startup'])
            ->get('/test-startup-route', fn () => 'ok');

        $response = $this->actingAs($startup)
            ->get('/test-startup-route');

        $response->assertOk();
    }

    public function test_admin_cannot_access_startup_only_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        Route::middleware(['auth', 'role:Startup'])
            ->get('/test-startup-route', fn () => 'ok');

        $response = $this->actingAs($admin)
            ->get('/test-startup-route');

        $response->assertForbidden();
    }

    public function test_is_admin_and_is_startup_helper_methods_work_correctly(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        $startup = User::factory()->create([
            'role' => 'Startup',
        ]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isStartup());

        $this->assertTrue($startup->isStartup());
        $this->assertFalse($startup->isAdmin());
    }
}

#asdasdas ashdashdas dasd
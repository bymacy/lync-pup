<?php

namespace Tests\Feature\Admin;

use App\Models\Coordinator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoordinatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_coordinator_index(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->get(route('admin.coordinators.index'));

        $response->assertOk();
    }

    public function test_startup_user_cannot_view_coordinator_index(): void
    {
        $startup = User::factory()->create(['role' => 'Startup']);

        $response = $this->actingAs($startup)->get(route('admin.coordinators.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_from_coordinator_index(): void
    {
        $response = $this->get(route('admin.coordinators.index'));

        $response->assertRedirect('/login');
    }

    public function test_admin_can_create_coordinator_with_fixed_role_title(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->post(route('admin.coordinators.store'), [
            'honorific' => 'Ma\'am',
            'first_name' => 'Jennie',
            'last_name' => 'Kim',
            'email' => 'jennie@pup.edu.ph',
            'phone' => '09171234567',
        ]);

        $response->assertRedirect(route('admin.coordinators.index'));
        $this->assertDatabaseHas('coordinators', [
            'first_name' => 'Jennie',
            'last_name' => 'Kim',
            'name' => "Ma'am Jennie Kim",
            'role_title' => 'Portfolio Coordinator',
        ]);
    }

    public function test_admin_can_update_coordinator(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $coordinator = Coordinator::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.coordinators.update', $coordinator), [
            'honorific' => 'Sir',
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

        $response->assertRedirect(route('admin.coordinators.index'));
        $this->assertDatabaseHas('coordinators', [
            'coordinator_id' => $coordinator->coordinator_id,
            'name' => 'Sir Updated Name',
        ]);
    }

    public function test_admin_can_delete_coordinator(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $coordinator = Coordinator::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.coordinators.destroy', $coordinator));

        $response->assertRedirect(route('admin.coordinators.index'));
        $this->assertDatabaseMissing('coordinators', ['coordinator_id' => $coordinator->coordinator_id]);
    }
}
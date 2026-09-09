<?php

namespace Tests\Feature\Admin;

use App\Models\Startup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Founder Application is now a read-only list — approve/reject no longer
 * happen here. Email verification alone activates a founder's account (see
 * Tests\Feature\Auth\EmailVerificationTest), and acceptance into the
 * incubation program happens later, via the evaluation Accept/Reject on the
 * Information Sheet (see Tests\Feature\Admin\InformationSheetTest).
 */
class FounderApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_and_reject_routes_no_longer_exist(): void
    {
        $this->assertFalse(Route::has('admin.founder-applications.approve'));
        $this->assertFalse(Route::has('admin.founder-applications.reject'));
    }

    public function test_admin_can_view_the_founder_application_list(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $founder = User::factory()->create(['role' => 'Startup', 'account_status' => 'Active']);
        Startup::factory()->create(['user_id' => $founder->id]);

        $response = $this->actingAs($admin)->get(route('admin.founder-applications.index'));

        $response->assertOk();
    }

    public function test_admin_can_delete_a_still_unverified_signup(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $founder = User::factory()->unverified()->create([
            'role' => 'Startup',
            'account_status' => 'Pending',
        ]);
        $startup = Startup::factory()->create(['user_id' => $founder->id]);

        $response = $this->actingAs($admin)->delete(route('admin.founder-applications.destroy', $startup));

        $response->assertRedirect(route('admin.founder-applications.index'));
        $this->assertModelMissing($startup);
        $this->assertModelMissing($founder);
    }

    public function test_admin_cannot_delete_an_already_verified_signup(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $founder = User::factory()->create(['role' => 'Startup', 'account_status' => 'Active']);
        $startup = Startup::factory()->create(['user_id' => $founder->id]);

        $response = $this->actingAs($admin)->delete(route('admin.founder-applications.destroy', $startup));

        $response->assertNotFound();
        $this->assertModelExists($startup);
    }
}

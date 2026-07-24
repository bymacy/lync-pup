<?php

namespace Tests\Feature\Admin;

use App\Models\Coordinator;
use App\Models\InformationSheet;
use App\Models\Startup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class StartupProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function makeStartup(string $approvalStatus = 'Approved'): Startup
    {
        $startup = Startup::factory()->create();
        InformationSheet::factory()->create([
            'startup_id' => $startup->startup_id,
            'approval_status' => $approvalStatus,
        ]);

        return $startup;
    }

    public function test_admin_can_view_startup_index(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->makeStartup();

        $response = $this->actingAs($admin)->get(route('admin.startups.index'));

        $response->assertOk();
    }

    public function test_startup_user_cannot_view_admin_startup_index(): void
    {
        $startupUser = User::factory()->create(['role' => 'Startup']);

        $response = $this->actingAs($startupUser)->get(route('admin.startups.index'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_from_startup_index(): void
    {
        $response = $this->get(route('admin.startups.index'));

        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_startup_profile(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $startup = $this->makeStartup();

        $response = $this->actingAs($admin)->get(route('admin.startups.show', $startup));

        $response->assertOk()->assertSee($startup->company_name);
    }

    public function test_admin_can_assign_coordinator_to_approved_startup(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $startup = $this->makeStartup('Approved');
        $coordinator = Coordinator::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.startups.coordinator.store', $startup), [
            'coordinator_id' => $coordinator->coordinator_id,
        ]);

        $response->assertRedirect(route('admin.startups.show', $startup));
        $this->assertDatabaseHas('coordinator_assignments', [
            'startup_id' => $startup->startup_id,
            'coordinator_id' => $coordinator->coordinator_id,
            'assignment_status' => 'Active',
        ]);
        $this->assertEquals('Active', $startup->fresh()->status);
    }

    public function test_pending_tab_filters_correctly(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->makeStartup('Pending');
        $this->makeStartup('Approved');

        $response = $this->actingAs($admin)->get(route('admin.startups.index', ['tab' => 'pending']));

        $response->assertOk();
        $response->assertViewHas('startups', fn ($startups) => $startups->count() === 1);
    }
    public function test_admin_can_request_pitch_deck(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $admin = User::factory()->create(['role' => 'Admin']);
        $startup = $this->makeStartup('Approved');

        $response = $this->actingAs($admin)->post(route('admin.startups.request-pitch-deck', $startup));

        $response->assertRedirect(route('admin.startups.show', $startup));
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\PitchDeckRequested::class);
        $this->assertNotNull($startup->fresh()->pitch_deck_requested_at);
    }
}
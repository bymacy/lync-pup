<?php

namespace Tests\Feature\Admin;

use App\Models\Mentor;
use App\Models\Roadblock;
use App\Models\Startup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoadblockTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser(): User
    {
        return User::factory()->create(['role' => 'Admin']);
    }

    protected function pendingRoadblock(): Roadblock
    {
        $user = User::factory()->create(['role' => 'Startup']);
        $startup = Startup::factory()->create(['user_id' => $user->id]);

        return Roadblock::factory()->create([
            'startup_id' => $startup->startup_id,
            'status' => 'Pending',
        ]);
    }

    public function test_admin_can_view_roadblock_management_page(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('admin.roadblocks.index'));

        $response->assertOk();
        $response->assertSee('Roadblock Management');
    }

    public function test_admin_can_assign_mentor_and_schedule_meeting(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();
        $mentor = Mentor::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.roadblocks.assign', $roadblock), [
            'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->addDay()->toDateString(),
            'meeting_start_time' => '08:00',
            'meeting_end_time' => '10:00',
            'meeting_platform' => 'Google Meet',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('roadblocks', [
            'roadblock_id' => $roadblock->roadblock_id,
            'status' => 'Scheduled',
            'mentor_id' => $mentor->mentor_id,
        ]);
    }

    public function test_scheduled_roadblock_moves_to_assessment_after_meeting_passes(): void
    {
        $roadblock = $this->pendingRoadblock();
        $mentor = Mentor::factory()->create();

        $roadblock->update([
            'status' => 'Scheduled',
            'mentor_id' => $mentor->mentor_id,
            'meeting_date' => now()->subDay()->toDateString(),
            'meeting_start_time' => '08:00',
            'meeting_end_time' => '10:00',
            'meeting_platform' => 'Google Meet',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ]);

        $this->assertTrue($roadblock->fresh()->isInAssessment());
    }

    public function test_admin_can_resolve_a_roadblock(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();
        $roadblock->update(['status' => 'Scheduled']);

        $response = $this->actingAs($admin)->post(route('admin.roadblocks.resolve', $roadblock));

        $response->assertRedirect();
        $this->assertDatabaseHas('roadblocks', [
            'roadblock_id' => $roadblock->roadblock_id,
            'status' => 'Resolved',
        ]);
    }

    public function test_admin_can_fail_a_roadblock(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();
        $roadblock->update(['status' => 'Scheduled']);

        $response = $this->actingAs($admin)->post(route('admin.roadblocks.fail', $roadblock));

        $response->assertRedirect();
        $this->assertDatabaseHas('roadblocks', [
            'roadblock_id' => $roadblock->roadblock_id,
            'status' => 'Failed',
        ]);
    }

    public function test_admin_can_recover_a_resolved_roadblock(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();
        $roadblock->update(['status' => 'Resolved', 'resolved_at' => now()]);

        $response = $this->actingAs($admin)->post(route('admin.roadblocks.recover', $roadblock));

        $response->assertRedirect();
        $this->assertDatabaseHas('roadblocks', [
            'roadblock_id' => $roadblock->roadblock_id,
            'status' => 'Scheduled',
        ]);
    }

    public function test_admin_can_delete_a_failed_roadblock(): void
    {
        $admin = $this->adminUser();
        $roadblock = $this->pendingRoadblock();
        $roadblock->update(['status' => 'Failed', 'failed_at' => now()]);

        $response = $this->actingAs($admin)->delete(route('admin.roadblocks.destroy', $roadblock));

        $response->assertRedirect();
        $this->assertDatabaseMissing('roadblocks', ['roadblock_id' => $roadblock->roadblock_id]);
    }

    public function test_founder_cannot_access_admin_roadblock_routes(): void
    {
        $user = User::factory()->create(['role' => 'Startup']);
        Startup::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('admin.roadblocks.index'));

        $response->assertForbidden();
    }
}

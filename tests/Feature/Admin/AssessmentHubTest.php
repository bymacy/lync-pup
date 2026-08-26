<?php

namespace Tests\Feature\Admin;

use App\Models\EvaluationSchedule;
use App\Models\InformationSheet;
use App\Models\Startup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentHubTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser(): User
    {
        return User::factory()->create(['role' => 'Admin']);
    }

    public function test_approving_a_startup_removes_it_from_todays_evaluations(): void
    {
        $admin = $this->adminUser();
        $startup = Startup::factory()->create();
        InformationSheet::factory()->create([
            'startup_id' => $startup->startup_id,
            'approval_status' => 'Pending',
        ]);
        EvaluationSchedule::create([
            'startup_id' => $startup->startup_id,
            'evaluation_date' => now(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        // Still pending — shows up under Today.
        $response = $this->actingAs($admin)->get(route('admin.assessment-hub.index'));
        $this->assertTrue(
            $response->viewData('todayEvaluations')->contains('startup_id', $startup->startup_id)
        );

        // Approve the information sheet.
        $this->actingAs($admin)->patch(route('admin.information-sheet.approve', $startup));

        // Now it should be gone from Today and present under Approved.
        $response = $this->actingAs($admin)->get(route('admin.assessment-hub.index'));
        $this->assertFalse(
            $response->viewData('todayEvaluations')->contains('startup_id', $startup->startup_id)
        );
        $this->assertTrue(
            $response->viewData('approvedStartups')->contains('startup_id', $startup->startup_id)
        );
    }

    public function test_approving_a_startup_from_upcoming_redirects_to_approved_and_leaves_upcoming(): void
    {
        $admin = $this->adminUser();
        $startup = Startup::factory()->create();
        InformationSheet::factory()->create([
            'startup_id' => $startup->startup_id,
            'approval_status' => 'Pending',
        ]);
        EvaluationSchedule::create([
            'startup_id' => $startup->startup_id,
            'evaluation_date' => now()->addDays(3),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        // Still pending — shows up under Upcoming.
        $response = $this->actingAs($admin)->get(route('admin.assessment-hub.index'));
        $this->assertTrue(
            $response->viewData('upcomingEvaluations')->contains('startup_id', $startup->startup_id)
        );

        // Approve from the show page reached via Upcoming > View.
        $approveResponse = $this->actingAs($admin)->patch(route('admin.information-sheet.approve', $startup));
        $approveResponse->assertRedirect(route('admin.assessment-hub.index', ['tab' => 'approved']));

        // Follow the redirect: gone from Upcoming, present under Approved.
        $response = $this->actingAs($admin)->get(route('admin.assessment-hub.index', ['tab' => 'approved']));
        $this->assertFalse(
            $response->viewData('upcomingEvaluations')->contains('startup_id', $startup->startup_id)
        );
        $this->assertTrue(
            $response->viewData('approvedStartups')->contains('startup_id', $startup->startup_id)
        );
    }

    public function test_approving_a_startup_removes_it_from_the_scheduled_today_widget(): void
    {
        $admin = $this->adminUser();
        $startup = Startup::factory()->create();
        InformationSheet::factory()->create([
            'startup_id' => $startup->startup_id,
            'approval_status' => 'Approved',
        ]);
        EvaluationSchedule::create([
            'startup_id' => $startup->startup_id,
            'evaluation_date' => now(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.assessment-hub.index'));

        $this->assertFalse(
            $response->viewData('scheduledToday')->contains('startup_id', $startup->startup_id)
        );
    }
}

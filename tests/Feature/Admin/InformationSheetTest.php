<?php

namespace Tests\Feature\Admin;

use App\Models\EvaluationSchedule;
use App\Models\InformationSheet;
use App\Models\Startup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformationSheetTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser(): User
    {
        return User::factory()->create(['role' => 'Admin']);
    }

    protected function makeStartup(): Startup
    {
        $startup = Startup::factory()->create();
        InformationSheet::factory()->create([
            'startup_id' => $startup->startup_id,
            'approval_status' => 'Pending',
            'mobile_no' => '09171234567',
        ]);

        return $startup;
    }

    public function test_admin_cannot_blank_a_previously_filled_field_once_evaluation_is_scheduled(): void
    {
        $admin = $this->adminUser();
        $startup = $this->makeStartup();
        EvaluationSchedule::create([
            'startup_id' => $startup->startup_id,
            'evaluation_date' => now()->addDays(3),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.information-sheet.update', $startup), [
            'surname' => 'Santos',
            'first_name' => 'Maria',
            'mobile_no' => '', // cleared — should be rejected instead of accepted.
        ]);

        $response->assertSessionHasErrors(['mobile_no']);
        $this->assertEquals('09171234567', $startup->informationSheet->fresh()->mobile_no);
    }

    public function test_admin_can_still_edit_after_the_evaluation_day_has_started(): void
    {
        $admin = $this->adminUser();
        $startup = $this->makeStartup();
        EvaluationSchedule::create([
            'startup_id' => $startup->startup_id,
            'evaluation_date' => now(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.information-sheet.update', $startup), [
            'surname' => 'Santos',
            'first_name' => 'Maria',
            'mobile_no' => '09171234567',
        ]);

        $response->assertRedirect(route('admin.information-sheet.show', $startup));
        $this->assertEquals('Santos', $startup->informationSheet->fresh()->surname);
    }

    public function test_admin_cannot_approve_a_startup_with_no_scheduled_evaluation(): void
    {
        $admin = $this->adminUser();
        $startup = $this->makeStartup();

        $response = $this->actingAs($admin)->patch(route('admin.information-sheet.approve', $startup));

        $response->assertForbidden();
        $this->assertEquals('Pending', $startup->informationSheet->fresh()->approval_status);
    }

    public function test_admin_can_approve_a_startup_once_an_evaluation_is_scheduled(): void
    {
        $admin = $this->adminUser();
        $startup = $this->makeStartup();
        EvaluationSchedule::create([
            'startup_id' => $startup->startup_id,
            'evaluation_date' => now()->addDays(3),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.information-sheet.approve', $startup));

        $response->assertRedirect(route('admin.assessment-hub.index', ['tab' => 'approved']));
        $this->assertEquals('Approved', $startup->informationSheet->fresh()->approval_status);
    }

    public function test_approve_button_is_disabled_when_no_evaluation_is_scheduled(): void
    {
        $admin = $this->adminUser();
        $startup = $this->makeStartup();

        $response = $this->actingAs($admin)->get(route('admin.information-sheet.show', $startup));

        $response->assertOk();
        $response->assertSee('Schedule an evaluation for this startup before approving.');
    }

    public function test_approve_button_is_enabled_once_an_evaluation_is_scheduled(): void
    {
        $admin = $this->adminUser();
        $startup = $this->makeStartup();
        EvaluationSchedule::create([
            'startup_id' => $startup->startup_id,
            'evaluation_date' => now()->addDays(3),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.information-sheet.show', $startup));

        $response->assertOk();
        $response->assertDontSee('Schedule an evaluation for this startup before approving.');
    }
}

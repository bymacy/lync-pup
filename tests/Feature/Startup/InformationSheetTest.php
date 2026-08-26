<?php

namespace Tests\Feature\Startup;

use App\Models\EvaluationSchedule;
use App\Models\InformationSheet;
use App\Models\Startup;
use App\Models\StartupReference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformationSheetTest extends TestCase
{
    use RefreshDatabase;

    protected function makeFounder(string $status = 'Pending'): array
    {
        // $status here is the InformationSheet's approval_status, not the
        // User's account_status — the latter must still be set explicitly
        // to 'Active': actingAs() keeps using this exact in-memory model
        // for every request in the test, and Eloquent never re-fetches a
        // model after create() to learn what default a column got at the
        // database level, so an omitted account_status reads back as null
        // in PHP even though the row itself says 'Active', which the
        // 'approved' middleware then treats as not approved and redirects
        // to /login.
        $user = User::factory()->create(['role' => 'Startup', 'account_status' => 'Active']);
        $startup = Startup::factory()->create(['user_id' => $user->id]);
        InformationSheet::factory()->create(['startup_id' => $startup->startup_id, 'approval_status' => $status]);

        return [$user, $startup];
    }

    public function test_founder_can_view_information_sheet(): void
    {
        [$user] = $this->makeFounder();

        $response = $this->actingAs($user)->get(route('startup.information-sheet.edit'));

        $response->assertOk();
    }

    public function test_saving_resets_approval_status_to_pending(): void
    {
        [$user, $startup] = $this->makeFounder('Pending');

        $this->actingAs($user)->patch(route('startup.information-sheet.update'), [
            'surname' => 'Santos',
            'first_name' => 'Maria',
        ]);

        $this->assertEquals('Pending', $startup->informationSheet->fresh()->approval_status);
    }

    public function test_founder_can_add_reference(): void
    {
        [$user, $startup] = $this->makeFounder();

        $response = $this->actingAs($user)->post(route('startup.references.store'), [
            'name' => 'Dr. Ana Cruz',
            'contact' => '09201234567',
        ]);

        $response->assertRedirect(route('startup.information-sheet.edit'));
        $this->assertDatabaseHas('startup_references', ['name' => 'Dr. Ana Cruz']);
    }

    public function test_founder_cannot_delete_another_startups_reference(): void
    {
        [$user] = $this->makeFounder();
        $otherStartup = Startup::factory()->create();
        $otherSheet = InformationSheet::factory()->create(['startup_id' => $otherStartup->startup_id]);
        $otherReference = StartupReference::factory()->create(['info_sheet_id' => $otherSheet->info_sheet_id]);

        $response = $this->actingAs($user)->delete(route('startup.references.destroy', $otherReference));

        $response->assertForbidden();
    }

    public function test_founder_cannot_edit_locked_approved_information_sheet(): void
    {
        [$user] = $this->makeFounder('Approved');

        $response = $this->actingAs($user)->patch(route('startup.information-sheet.update'), [
            'surname' => 'Attempted Change',
            'first_name' => 'Still Attempted',
        ]);

        $response->assertForbidden();
    }

    public function test_founder_can_leave_fields_blank_before_any_evaluation_is_scheduled(): void
    {
        [$user, $startup] = $this->makeFounder();
        $startup->informationSheet->update(['mobile_no' => '09171234567']);

        $response = $this->actingAs($user)->patch(route('startup.information-sheet.update'), [
            'surname' => 'Santos',
            'first_name' => 'Maria',
            'mobile_no' => '', // field cleared, as a real form would submit it.
        ]);

        $response->assertRedirect(route('startup.information-sheet.edit'));
        $this->assertNull($startup->informationSheet->fresh()->mobile_no);
    }

    public function test_founder_cannot_blank_a_previously_filled_field_once_evaluation_is_scheduled(): void
    {
        [$user, $startup] = $this->makeFounder();
        $startup->informationSheet->update(['mobile_no' => '09171234567']);
        EvaluationSchedule::create([
            'startup_id' => $startup->startup_id,
            'evaluation_date' => now()->addDays(3),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($user)->patch(route('startup.information-sheet.update'), [
            'surname' => 'Santos',
            'first_name' => 'Maria',
            'mobile_no' => '', // cleared — should now be rejected instead of accepted.
        ]);

        $response->assertSessionHasErrors(['mobile_no']);
        $this->assertEquals('09171234567', $startup->informationSheet->fresh()->mobile_no);
    }

    public function test_founder_can_replace_a_field_once_evaluation_is_scheduled(): void
    {
        [$user, $startup] = $this->makeFounder();
        $startup->informationSheet->update(['mobile_no' => '09171234567']);
        EvaluationSchedule::create([
            'startup_id' => $startup->startup_id,
            'evaluation_date' => now()->addDays(3),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($user)->patch(route('startup.information-sheet.update'), [
            'surname' => 'Santos',
            'first_name' => 'Maria',
            'mobile_no' => '09209876543',
        ]);

        $response->assertRedirect(route('startup.information-sheet.edit'));
        $this->assertEquals('09209876543', $startup->informationSheet->fresh()->mobile_no);
    }

    public function test_founder_is_locked_out_once_the_evaluation_day_starts(): void
    {
        [$user, $startup] = $this->makeFounder();
        EvaluationSchedule::create([
            'startup_id' => $startup->startup_id,
            'evaluation_date' => now(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($user)->patch(route('startup.information-sheet.update'), [
            'surname' => 'Attempted Change',
            'first_name' => 'Still Attempted',
        ]);

        $response->assertForbidden();
    }

    public function test_founder_is_not_locked_by_a_cancelled_evaluation(): void
    {
        [$user, $startup] = $this->makeFounder();
        EvaluationSchedule::create([
            'startup_id' => $startup->startup_id,
            'evaluation_date' => now(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Cancelled',
        ]);

        $response = $this->actingAs($user)->patch(route('startup.information-sheet.update'), [
            'surname' => 'Santos',
            'first_name' => 'Maria',
        ]);

        $response->assertRedirect(route('startup.information-sheet.edit'));
    }
}
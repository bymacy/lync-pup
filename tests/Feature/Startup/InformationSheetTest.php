<?php

namespace Tests\Feature\Startup;

use App\Models\InformationSheet;
use App\Models\Startup;
use App\Models\StartupReference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformationSheetTest extends TestCase
{
    use RefreshDatabase;

    protected function makeFounder(): array
    {
        $user = User::factory()->create(['role' => 'Startup']);
        $startup = Startup::factory()->create(['user_id' => $user->id]);
        InformationSheet::factory()->create(['startup_id' => $startup->startup_id, 'approval_status' => 'Approved']);

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
        [$user, $startup] = $this->makeFounder();

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
}
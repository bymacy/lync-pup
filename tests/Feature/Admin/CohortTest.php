<?php

namespace Tests\Feature\Admin;

use App\Models\Cohort;
use App\Models\Startup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CohortTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'Admin']);
    }

    /**
     * No CohortFactory exists — the create_cohorts_table migration itself
     * seeds cohorts 1-5 ("Cohort 1".."Cohort 5") on every fresh migration, so
     * tests build on those pre-seeded rows rather than the factory.
     */
    public function test_cannot_create_a_cohort_with_a_name_already_in_use(): void
    {
        // "Cohort 1" already exists from the cohorts table's own seeding.
        $response = $this->actingAs($this->admin())->post(route('admin.cohorts.store'), [
            'label' => 'Cohort 1',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ]);

        $response->assertSessionHasErrors('label');
        $this->assertSame(1, Cohort::where('label', 'Cohort 1')->count());
    }

    public function test_can_create_a_cohort_with_a_new_unique_name(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.cohorts.store'), [
            'label' => 'Cohort 6',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ]);

        $response->assertSessionDoesntHaveErrors('label');
        $this->assertDatabaseHas('cohorts', ['label' => 'Cohort 6']);
    }

    public function test_cannot_rename_a_cohort_to_a_name_already_used_by_another_cohort(): void
    {
        $cohort2 = Cohort::where('number', 2)->firstOrFail();

        $response = $this->actingAs($this->admin())->patch(route('admin.cohorts.update', $cohort2), [
            'label' => 'Cohort 1',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ]);

        $response->assertSessionHasErrors('label');
        $this->assertSame('Cohort 2', $cohort2->fresh()->label);
    }

    public function test_can_save_a_cohort_with_its_own_unchanged_name(): void
    {
        $cohort2 = Cohort::where('number', 2)->firstOrFail();

        $response = $this->actingAs($this->admin())->patch(route('admin.cohorts.update', $cohort2), [
            'label' => 'Cohort 2',
            'description' => 'Updated description.',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame('Updated description.', $cohort2->fresh()->description);
    }

    /**
     * Regression coverage for the "All Cohort" cross-module bug: selecting a
     * cohort on one page must stay selected when navigating to a completely
     * different module with no '?cohort=' in the URL at all.
     */
    public function test_selected_cohort_persists_across_modules_without_a_query_param(): void
    {
        $admin = $this->admin();
        $cohort2 = Cohort::where('number', 2)->firstOrFail();
        $cohort3 = Cohort::where('number', 3)->firstOrFail();

        Startup::factory()->create(['cohort_id' => $cohort2->cohort_id, 'cohort_number' => 2]);
        Startup::factory()->create(['cohort_id' => $cohort3->cohort_id, 'cohort_number' => 3]);

        // Select Cohort 2 on the Dashboard.
        $this->actingAs($admin)->get(route('dashboard', ['cohort' => $cohort2->cohort_id]));

        // Navigate to Startup Profile with no cohort param at all — the
        // selection should still be Cohort 2, not "All Cohort".
        $response = $this->actingAs($admin)->get(route('admin.startups.index'));

        $response->assertOk();
        $response->assertViewHas('selectedCohortId', $cohort2->cohort_id);
        $breakdown = $response->viewData('cohortBreakdown');
        $this->assertCount(1, $breakdown);
        $this->assertSame('Cohort 2', $breakdown->first()['label']);
    }

    public function test_explicit_all_cohort_clears_a_previously_selected_cohort(): void
    {
        $admin = $this->admin();
        $cohort2 = Cohort::where('number', 2)->firstOrFail();

        $this->actingAs($admin)->get(route('dashboard', ['cohort' => $cohort2->cohort_id]));

        // Explicit empty value, same as the "All Cohort" link's '?cohort='.
        $response = $this->actingAs($admin)->get(route('admin.startups.index', ['cohort' => '']));

        $response->assertOk();
        $response->assertViewHas('selectedCohortId', null);
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\Cohort;
use App\Models\Startup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FounderApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCohort(): Cohort
    {
        return Cohort::create([
            'number' => 1,
            'label' => 'Cohort 1',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'status' => 'Active',
        ]);
    }

    /**
     * Regression test: an admin used to be able to approve a founder
     * application even though that founder never verified their email —
     * see FounderApplicationController::approve().
     */
    public function test_cannot_approve_an_application_whose_email_is_not_verified(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $founder = User::factory()->unverified()->create([
            'role' => 'Startup',
            'account_status' => 'Pending',
        ]);
        $startup = Startup::factory()->create(['user_id' => $founder->id]);
        $cohort = $this->makeCohort();

        $response = $this->actingAs($admin)->post(
            route('admin.founder-applications.approve', $startup),
            ['cohort_id' => $cohort->cohort_id]
        );

        $response->assertRedirect(route('admin.founder-applications.index'));
        $response->assertSessionHas('error');
        $this->assertTrue($founder->fresh()->isPendingApproval());
        $this->assertNull($startup->fresh()->cohort_id);
    }

    public function test_can_approve_an_application_once_email_is_verified(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'Admin']);
        $founder = User::factory()->create([
            'role' => 'Startup',
            'account_status' => 'Pending',
        ]);
        $startup = Startup::factory()->create(['user_id' => $founder->id]);
        $cohort = $this->makeCohort();

        $response = $this->actingAs($admin)->post(
            route('admin.founder-applications.approve', $startup),
            ['cohort_id' => $cohort->cohort_id]
        );

        $response->assertRedirect(route('admin.founder-applications.index'));
        $this->assertTrue($founder->fresh()->isApprovedAccount());
        $this->assertSame($cohort->cohort_id, $startup->fresh()->cohort_id);
    }
}

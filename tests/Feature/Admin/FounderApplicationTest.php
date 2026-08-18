<?php

namespace Tests\Feature\Admin;

use App\Models\Cohort;
use App\Models\Coordinator;
use App\Models\Startup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FounderApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'Admin']);
    }

    private function pendingFounder(): Startup
    {
        $founder = User::factory()->create(['role' => 'Startup', 'account_status' => 'Pending']);

        return Startup::factory()->create(['user_id' => $founder->id]);
    }

    public function test_non_admin_cannot_view_founder_applications(): void
    {
        $founder = User::factory()->create(['role' => 'Startup', 'account_status' => 'Active']);

        $response = $this->actingAs($founder)->get(route('admin.founder-applications.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_founder_applications_with_correct_counts(): void
    {
        $this->pendingFounder();

        $approvedFounder = User::factory()->create(['role' => 'Startup', 'account_status' => 'Active']);
        Startup::factory()->create(['user_id' => $approvedFounder->id]);

        $rejectedFounder = User::factory()->create(['role' => 'Startup', 'account_status' => 'Rejected']);
        Startup::factory()->create(['user_id' => $rejectedFounder->id]);

        $response = $this->actingAs($this->admin())->get(route('admin.founder-applications.index'));

        $response->assertOk();
        $response->assertViewHas('totals', function ($totals) {
            return $totals['total'] === 3
                && $totals['pending'] === 1
                && $totals['approved'] === 1
                && $totals['rejected'] === 1;
        });
    }

    public function test_pending_tab_only_shows_pending_applications(): void
    {
        $this->pendingFounder();

        $approvedFounder = User::factory()->create(['role' => 'Startup', 'account_status' => 'Active']);
        Startup::factory()->create(['user_id' => $approvedFounder->id, 'company_name' => 'Already Approved Co']);

        $response = $this->actingAs($this->admin())->get(route('admin.founder-applications.index', ['tab' => 'pending']));

        $response->assertOk();
        $response->assertDontSee('Already Approved Co');
    }

    public function test_admin_can_approve_a_pending_application(): void
    {
        Mail::fake();

        $startup = $this->pendingFounder();
        $cohort = Cohort::first();
        $coordinator = Coordinator::factory()->create();

        $response = $this->actingAs($this->admin())->post(route('admin.founder-applications.approve', $startup), [
            'cohort_id' => $cohort->cohort_id,
            'coordinator_id' => $coordinator->coordinator_id,
            'admin_remarks' => 'Looks good, welcome aboard!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('application_result', function ($result) {
            return $result['type'] === 'approved';
        });

        $startup->refresh();
        $this->assertSame('Active', $startup->user->fresh()->account_status);
        $this->assertSame($cohort->cohort_id, $startup->cohort_id);
        $this->assertSame($cohort->number, $startup->cohort_number);
        $this->assertSame('Looks good, welcome aboard!', $startup->admin_remarks);
        $this->assertNotNull($startup->application_decided_at);

        $this->assertDatabaseHas('coordinator_assignments', [
            'startup_id' => $startup->startup_id,
            'coordinator_id' => $coordinator->coordinator_id,
            'assignment_status' => 'Active',
        ]);

        Mail::assertSent(\App\Mail\FounderApplicationApproved::class);
    }

    public function test_approving_without_a_cohort_fails_validation(): void
    {
        Mail::fake();

        $startup = $this->pendingFounder();

        $response = $this->actingAs($this->admin())->post(route('admin.founder-applications.approve', $startup), []);

        $response->assertSessionHasErrors('cohort_id');
        $this->assertSame('Pending', $startup->user->fresh()->account_status);
        Mail::assertNotSent(\App\Mail\FounderApplicationApproved::class);
    }

    public function test_admin_can_reject_a_pending_application(): void
    {
        Mail::fake();

        $startup = $this->pendingFounder();

        $response = $this->actingAs($this->admin())->post(route('admin.founder-applications.reject', $startup), [
            'rejection_reason' => 'Incomplete requirements',
            'admin_remarks' => 'Please reapply next cohort.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('application_result', function ($result) {
            return $result['type'] === 'rejected';
        });

        $startup->refresh();
        $this->assertSame('Rejected', $startup->user->fresh()->account_status);
        $this->assertSame('Incomplete requirements', $startup->rejection_reason);
        $this->assertSame('Please reapply next cohort.', $startup->admin_remarks);
        $this->assertNotNull($startup->application_decided_at);

        Mail::assertSent(\App\Mail\FounderApplicationRejected::class);
    }

    public function test_rejecting_without_a_reason_fails_validation(): void
    {
        Mail::fake();

        $startup = $this->pendingFounder();

        $response = $this->actingAs($this->admin())->post(route('admin.founder-applications.reject', $startup), []);

        $response->assertSessionHasErrors('rejection_reason');
        $this->assertSame('Pending', $startup->user->fresh()->account_status);
        Mail::assertNotSent(\App\Mail\FounderApplicationRejected::class);
    }

    public function test_rejected_founder_still_cannot_log_in(): void
    {
        Mail::fake();

        $startup = $this->pendingFounder();

        $this->actingAs($this->admin())->post(route('admin.founder-applications.reject', $startup), [
            'rejection_reason' => 'Not a good fit',
        ]);

        // actingAs() leaves the admin authenticated for the rest of this
        // test — log out first so the login attempt below is a genuine
        // guest request, matching the "reject then try to sign back in"
        // scenario this test is meant to cover.
        auth()->logout();

        $response = $this->post('/login', [
            'email' => $startup->user->fresh()->email,
            'password' => 'password',
            'role' => 'Startup',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Regression test: approving/rejecting an application that's already
     * been decided (e.g. a double-submitted form, or a stale/cached
     * "Pending" page resubmitted after the fact) must redirect back with a
     * friendly flash message instead of a bare 404.
     */
    public function test_approving_an_already_decided_application_redirects_gracefully(): void
    {
        Mail::fake();

        $startup = $this->pendingFounder();
        $cohort = Cohort::first();

        // First approval succeeds as normal.
        $this->actingAs($admin = $this->admin())->post(route('admin.founder-applications.approve', $startup), [
            'cohort_id' => $cohort->cohort_id,
        ]);

        // A second attempt on the same (now-approved) application.
        $response = $this->actingAs($admin)->post(route('admin.founder-applications.approve', $startup), [
            'cohort_id' => $cohort->cohort_id,
        ]);

        $response->assertRedirect(route('admin.founder-applications.index'));
        $response->assertSessionHas('error');
        Mail::assertSent(\App\Mail\FounderApplicationApproved::class, 1);
    }

    public function test_rejecting_an_already_decided_application_redirects_gracefully(): void
    {
        Mail::fake();

        $startup = $this->pendingFounder();

        $this->actingAs($admin = $this->admin())->post(route('admin.founder-applications.reject', $startup), [
            'rejection_reason' => 'Not a good fit',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.founder-applications.reject', $startup), [
            'rejection_reason' => 'Not a good fit',
        ]);

        $response->assertRedirect(route('admin.founder-applications.index'));
        $response->assertSessionHas('error');
        Mail::assertSent(\App\Mail\FounderApplicationRejected::class, 1);
    }
}

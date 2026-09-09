<?php

namespace Database\Seeders;

use App\Models\Cohort;
use App\Models\Coordinator;
use App\Models\CoordinatorAssignment;
use App\Models\EvaluationSchedule;
use App\Models\InformationSheet;
use App\Models\Startup;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds a single founder account with everything unlocked — Active account,
 * verified email, complete profile, submitted + Approved Information Sheet,
 * an active coordinator assignment, and a completed evaluation — so it can
 * be used to click through every founder-side page with nothing gated.
 *
 * Not part of the default DatabaseSeeder chain (idempotent via
 * firstOrCreate, safe to re-run): php artisan db:seed --class=ApprovedFounderSeeder
 */
class ApprovedFounderSeeder extends Seeder
{
    public function run(): void
    {
        $founder = User::firstOrCreate(
            ['email' => 'founder.approved@test.com'],
            ['name' => 'Approved Founder', 'password' => 'password', 'role' => 'Startup']
        );
        $founder->update([
            'account_status' => 'Active',
            'email_verified_at' => now(),
        ]);

        // Cohort assignment now only happens inside
        // InformationSheetController::approve(), which always sets
        // cohort_id and cohort_number together from a real Cohort row — so
        // this fully-Approved seed startup needs a real Cohort behind it
        // too, not just a bare number.
        $cohort1 = Cohort::where('number', 1)->firstOrFail();

        $startup = Startup::firstOrCreate(
            ['company_name' => 'FullyApproved Ventures'],
            [
                'user_id' => $founder->id,
                'industry_sector' => 'FinTech',
                'business_description' => 'A fully seeded, fully approved test startup used to exercise every founder-side feature.',
                'contact_phone' => '09171234567',
                'location' => 'Manila, Philippines',
                'website' => 'https://fullyapproved.example.com',
                // isProfileComplete() checks this is non-blank — points at an
                // existing public storage asset so the avatar doesn't 404.
                'startup_photo_path' => 'startup-photos/placeholder.png',
            ]
        );
        $startup->update([
            'user_id' => $founder->id,
            'cohort_id' => $cohort1->cohort_id,
            'cohort_number' => $cohort1->number,
            'application_decided_at' => now()->subDays(10),
        ]);

        InformationSheet::updateOrCreate(
            ['startup_id' => $startup->startup_id],
            [
                'business_description' => $startup->business_description,
                'target_market' => 'SMEs and individual consumers across the Philippines.',
                'problem_statement' => 'Founders need a fast, fully-unlocked seed account to test every page.',
                'solution_offered' => 'A one-shot seeder that builds a complete, Approved founder account.',
                'submission_date' => now()->subDays(30),
                'approval_status' => 'Approved',
                'approved_at' => now()->subDays(10),
            ]
        );

        $coordinator = Coordinator::firstOrCreate(
            ['email' => 'coordinator.test@pup.edu.ph'],
            [
                'honorific' => 'Sir',
                'first_name' => 'Test',
                'last_name' => 'Coordinator',
                'name' => 'Sir Test Coordinator',
                'role_title' => 'Portfolio Coordinator',
                'phone' => '09561234567',
            ]
        );

        CoordinatorAssignment::firstOrCreate(
            ['startup_id' => $startup->startup_id],
            [
                'coordinator_id' => $coordinator->coordinator_id,
                'assigned_date' => now()->subDays(10),
                'assignment_status' => 'Active',
            ]
        );

        // A completed evaluation in the past so the Meetings page has real
        // history to show, rather than an empty state.
        EvaluationSchedule::firstOrNew(['startup_id' => $startup->startup_id])
            ->fill([
                'evaluation_date' => now()->subDays(15)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '11:00',
                'status' => 'Completed',
                'notes' => 'Seeded evaluation for the fully-approved test account.',
            ])->save();

        $this->command?->info('Approved founder account ready: founder.approved@test.com / password');
    }
}

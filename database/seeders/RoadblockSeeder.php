<?php

namespace Database\Seeders;

use App\Models\EvaluationSchedule;
use App\Models\Mentor;
use App\Models\Roadblock;
use App\Models\Startup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds one Roadblock in every status/tab the Roadblock Management screens
 * can show, so a new user can see and click through the full feature
 * without having to manually create test data first:
 *
 *   Founder  > Submission > Roadblock  -> the Pending roadblock
 *   Founder  > Submission > Archive    -> Resolved + Failed roadblocks
 *   Founder  > Meeting                 -> the "today" and "upcoming" Scheduled roadblocks
 *   Admin    > Manage Roadblock        -> Pending list + Upcoming mentorship table
 *   Admin    > Scheduled Today         -> the "today" Scheduled roadblock
 *   Admin    > Archive > Assessment    -> the "yesterday" Scheduled roadblock (meeting already over)
 *   Admin    > Archive > Resolved      -> the Resolved roadblock
 *   Admin    > Archive > Failed        -> the Failed roadblock
 *   Admin    > Mentor Profile          -> reuses the mentors DevDataSeeder already creates
 */
class RoadblockSeeder extends Seeder
{
    public function run(): void
    {
        // Guarantee the startups, founders, and mentors this seeder relies on
        // exist. Safe to call again — everything in DevDataSeeder is firstOrCreate.
        $this->call(DevDataSeeder::class);

        $agriSense = Startup::where('company_name', 'AgriSense PH')->first();
        $ecoWatt = Startup::where('company_name', 'EcoWatt Solutions')->first();

        if (! $agriSense || ! $ecoWatt) {
            $this->command->error('RoadblockSeeder: expected startups not found. Run DevDataSeeder first.');
            return;
        }

        $jennie = Mentor::where('contact_email', 'cruz@gmail.com')->first();
        $argee = Mentor::where('contact_email', 'itsargeebueno@gmail.com')->first();

        // 1) Pending — freshly submitted, not yet triaged.
        Roadblock::firstOrCreate(
            [
                'startup_id' => $agriSense->startup_id,
                'problem_category' => 'Technical Support',
                'status' => 'Pending',
            ],
            [
                'description' => 'Our booking system keeps crashing when more than 10 users check out at once. We need technical guidance before our next demo day.',
            ]
        );

        // 2) Scheduled — meeting later today.
        // Note: this assumes the seeder runs before the last daily slot (3 PM).
        // If run later in the day, this meeting's time will already be in the
        // past and it will show up under Archive > Assessment instead.
        $remainingSlotsToday = collect(EvaluationSchedule::TIME_SLOTS)
            ->first(fn ($slot) => Carbon::createFromFormat('H:i', $slot[0])->gt(now()));

        if (! $remainingSlotsToday) {
            $this->command->warn('RoadblockSeeder: no time slots left today — the "Scheduled Today" sample roadblock will land in Archive > Assessment instead. Re-run earlier in the day to see it under "Scheduled Today".');
        }

        $todaySlot = $remainingSlotsToday ?? collect(EvaluationSchedule::TIME_SLOTS)->last();

        Roadblock::firstOrCreate(
            [
                'startup_id' => $agriSense->startup_id,
                'problem_category' => 'Business Development',
                'status' => 'Scheduled',
                'meeting_date' => now()->toDateString(),
            ],
            [
                'description' => 'We need help refining our go-to-market pricing strategy before we approach retail partners.',
                'mentor_id' => $jennie?->mentor_id,
                'meeting_start_time' => $todaySlot[0],
                'meeting_end_time' => $todaySlot[1],
                'meeting_platform' => 'Google Meet',
                'meeting_link' => 'https://meet.google.com/lync-demo-today',
            ]
        );

        // 3) Scheduled — meeting was yesterday and hasn't been closed out yet.
        Roadblock::firstOrCreate(
            [
                'startup_id' => $agriSense->startup_id,
                'problem_category' => 'Market Research',
                'status' => 'Scheduled',
                'meeting_date' => now()->subDay()->toDateString(),
            ],
            [
                'description' => 'We need to validate whether there is real demand for our product in the Visayas region before expanding operations there.',
                'mentor_id' => $argee?->mentor_id,
                'meeting_start_time' => '10:00',
                'meeting_end_time' => '11:00',
                'meeting_platform' => 'Zoom',
                'meeting_link' => 'https://zoom.us/j/lync-demo-yesterday',
            ]
        );

        // 4) Resolved — meeting already happened and was closed out successfully.
        Roadblock::firstOrCreate(
            [
                'startup_id' => $agriSense->startup_id,
                'problem_category' => 'Strategy Consultant',
                'status' => 'Resolved',
            ],
            [
                'description' => 'Our accounting process is a mess and we need guidance setting up a proper bookkeeping system before our next investor update.',
                'mentor_id' => $jennie?->mentor_id,
                'meeting_date' => now()->subDays(3)->toDateString(),
                'meeting_start_time' => '13:00',
                'meeting_end_time' => '14:00',
                'meeting_platform' => 'Microsoft Teams',
                'meeting_link' => 'https://teams.microsoft.com/lync-demo-resolved',
                'resolved_at' => now()->subDays(3),
            ]
        );

        // 5) Failed — meeting happened but the roadblock could not be resolved.
        Roadblock::firstOrCreate(
            [
                'startup_id' => $agriSense->startup_id,
                'problem_category' => 'Others',
                'problem_category_other' => 'Legal Counseling',
                'status' => 'Failed',
            ],
            [
                'description' => 'We need help drafting an IP licensing agreement with a supplier and are unsure of the legal terms involved.',
                'mentor_id' => $argee?->mentor_id,
                'meeting_date' => now()->subDays(5)->toDateString(),
                'meeting_start_time' => '09:00',
                'meeting_end_time' => '10:00',
                'meeting_platform' => 'Google Meet',
                'meeting_link' => 'https://meet.google.com/lync-demo-failed',
                'failed_at' => now()->subDays(5),
            ]
        );

        // 6) Scheduled — meeting a few days from now, on a second startup, so
        // the "Manage Roadblock" upcoming mentorship table has more than a
        // single row and the EcoWatt founder also has something on Meetings.
        Roadblock::firstOrCreate(
            [
                'startup_id' => $ecoWatt->startup_id,
                'problem_category' => 'Technical Support',
                'status' => 'Scheduled',
                'meeting_date' => now()->addDays(3)->toDateString(),
            ],
            [
                'description' => 'Our IoT sensor firmware needs a code review before we scale up to our next batch of pilot customers.',
                'mentor_id' => $jennie?->mentor_id,
                'meeting_start_time' => '09:00',
                'meeting_end_time' => '10:00',
                'meeting_platform' => 'Zoom',
                'meeting_link' => 'https://zoom.us/j/lync-demo-upcoming',
            ]
        );

        $this->command->info('Roadblock sample data seeded successfully (Pending, Scheduled-Today, Assessment, Resolved, Failed, Upcoming).');
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Startup;
use App\Models\Coordinator;
use App\Models\InformationSheet;
use App\Models\TeamMember;
use App\Models\ReadinessLevelAssessment;
use App\Models\IncubationInvolvement;
use App\Models\LdIntervention;
use App\Models\StartupReference;
use App\Models\Mentor;
use App\Models\EvaluationSchedule;
use App\Support\ReadinessRubric;
use Illuminate\Database\Seeder;

class DevDataSeeder extends Seeder
{
    public function run(): void
    {
        // Admin account
        User::firstOrCreate(
            ['email' => 'admin@pup.edu.ph'],
            ['name' => 'TBI Administrator', 'password' => 'password', 'role' => 'Admin']
        );

        // Founder test account — verified + Active so it can log straight in.
        // This one is meant for general day-to-day testing (dashboard,
        // profile, roadblocks, etc.), not for exercising the verify-email or
        // admin-approval screens themselves — FounderApplicationSeeder's
        // accounts exist specifically for that instead.
        //
        // firstOrCreate()'s attributes only apply the first time this row is
        // created — anyone who already ran this seeder before verified/Active
        // were added here is stuck with an unverified row that a re-seed
        // alone won't fix. The explicit update() below forces it every run,
        // so re-running `php artisan db:seed` actually repairs it.
        $founder = User::firstOrCreate(
            ['email' => 'founder@test.com'],
            ['name' => 'Maria Santos', 'password' => 'password', 'role' => 'Startup']
        );
        $founder->update(['account_status' => 'Active', 'email_verified_at' => now()]);

        // Coordinators (only seed if none exist yet)
        if (Coordinator::count() === 0) {
            Coordinator::factory()->count(3)->create();
        }

        // AgriSense PH - Pending
        $pending = Startup::firstOrCreate(
            ['company_name' => 'AgriSense PH'],
            [
                'user_id' => $founder->id,
                'industry_sector' => 'AgriTech',
                'cohort_number' => 3,
                'contact_phone' => '09171234567',
                'location' => 'Mandaluyong City, PH',
            ]
        );
        $pending->update(['user_id' => $founder->id]);

        $sheet = InformationSheet::firstOrCreate(
            ['startup_id' => $pending->startup_id],
            ['approval_status' => 'Pending', 'business_description' => 'Placeholder']
        );

        $sheet->update([
            'surname' => 'Santos', 'first_name' => 'Maria', 'middle_name' => 'Reyes',
            'name_extension' => 'N/A', 'height_m' => '1.65', 'weight_kg' => '58', 'blood_type' => 'O+',
            'gsis_no' => '1234567890', 'pagibig_no' => '1234-5678-9012', 'philhealth_no' => '12-345678901-2',
            'sss_no' => '12-3456789-0', 'residential_address' => 'B11 L3 Sample St., Mandaluyong City',
            'permanent_address' => 'B11 L3 Sample St., Mandaluyong City', 'sex' => 'Female',
            'civil_status' => 'Single', 'citizenship_by_birth' => 'Filipino', 'citizenship_dual' => 'N/A',
            'place_of_birth' => 'Manila', 'date_of_birth' => '1998-05-14', 'mobile_no' => '09171234567',
            'founder_email' => 'maria.santos@agrisense.ph',
            'secondary_school' => 'Manila High School', 'secondary_degree_course' => 'N/A',
            'secondary_highest_level_unit' => 'N/A', 'secondary_year_graduated' => '2014',
            'vocational_school' => 'N/A', 'vocational_degree_course' => 'N/A',
            'vocational_highest_level_unit' => 'N/A', 'vocational_year_graduated' => 'N/A',
            'college_school' => 'Polytechnic University of the Philippines',
            'college_degree_course' => 'BS Computer Science', 'college_highest_level_unit' => "Bachelor's Degree",
            'college_year_graduated' => '2018',
            'graduate_school' => 'Polytechnic University of the Philippines',
            'graduate_degree_course' => 'Master in Business Administration',
            'graduate_highest_level_unit' => "Master's Degree", 'graduate_year_graduated' => '2021',
            'scholarships_academic_honors' => "Dean's Lister, 2016-2018\nDOST Scholarship Grantee",
            'sec_registration' => 'CS201812345', 'business_id_number' => 'BID-0098765',
            'dti_registration_number' => 'DTI-0054321', 'business_tin' => '123-456-789-000',
            'non_academic_distinctions' => 'Best Startup Pitch, PUP Innovation Summit 2023',
            'membership_associations' => 'Philippine Startup Founders Network',
            'date_accomplished' => '2026-07-10', 'portfolio_manager' => 'Engr. Tristan Velardo',
            'cohort_no' => 'Cohort 3', 'endorsed_by' => 'Sir Erwin', 'endorsement_date' => '2026-07-11',
        ]);

        if (TeamMember::where('startup_id', $pending->startup_id)->count() === 0) {
            TeamMember::create(['startup_id' => $pending->startup_id, 'full_name' => 'Maria Santos', 'designation' => 'CEO', 'role' => 'CEO', 'phone' => '09171234567', 'address' => 'Mandaluyong City', 'date_of_birth' => '1998-05-14', 'email' => 'maria@agrisense.ph', 'citizenship' => 'Filipino', 'sex' => 'Female', 'civil_status' => 'Single']);
            TeamMember::create(['startup_id' => $pending->startup_id, 'full_name' => 'Juan Dela Cruz', 'designation' => 'CTO', 'role' => 'CTO', 'phone' => '09181234567', 'address' => 'Quezon City', 'date_of_birth' => '1997-03-22', 'email' => 'juan@agrisense.ph', 'citizenship' => 'Filipino', 'sex' => 'Male', 'civil_status' => 'Single']);
            TeamMember::create(['startup_id' => $pending->startup_id, 'full_name' => 'Liza Tan', 'designation' => 'Operations Lead', 'role' => 'Operations', 'phone' => '09191234567', 'address' => 'Pasig City', 'date_of_birth' => '1999-11-02', 'email' => 'liza@agrisense.ph', 'citizenship' => 'Filipino', 'sex' => 'Female', 'civil_status' => 'Single']);
        }

        if (IncubationInvolvement::where('info_sheet_id', $sheet->info_sheet_id)->count() === 0) {
            IncubationInvolvement::create(['info_sheet_id' => $sheet->info_sheet_id, 'organization_name_address' => 'DTI Negosyo Center, Manila', 'date_from' => '2023-01-01', 'date_to' => '2023-06-30', 'number_of_hours' => '80', 'incubation_program_focus' => 'Business Development']);
            IncubationInvolvement::create(['info_sheet_id' => $sheet->info_sheet_id, 'organization_name_address' => 'QBO Innovation Hub, Makati', 'date_from' => '2023-07-01', 'date_to' => '2023-12-15', 'number_of_hours' => '120', 'incubation_program_focus' => 'Tech Acceleration']);
        }

        if (LdIntervention::where('info_sheet_id', $sheet->info_sheet_id)->count() === 0) {
            LdIntervention::create(['info_sheet_id' => $sheet->info_sheet_id, 'title' => 'Pitch Deck Bootcamp', 'date_from' => '2023-08-01', 'date_to' => '2023-08-03', 'number_of_hours' => '24', 'conducted_sponsored_by' => 'PUP-TBIDO']);
            LdIntervention::create(['info_sheet_id' => $sheet->info_sheet_id, 'title' => 'Financial Literacy for Startups', 'date_from' => '2023-09-10', 'date_to' => '2023-09-11', 'number_of_hours' => '16', 'conducted_sponsored_by' => 'DTI']);
        }

        if (StartupReference::where('info_sheet_id', $sheet->info_sheet_id)->count() === 0) {
            StartupReference::create(['info_sheet_id' => $sheet->info_sheet_id, 'name' => 'Dr. Ana Cruz', 'contact' => '09201234567', 'email' => 'ana.cruz@pup.edu.ph', 'address' => 'PUP Sta. Mesa, Manila']);
            StartupReference::create(['info_sheet_id' => $sheet->info_sheet_id, 'name' => 'Engr. Paolo Reyes', 'contact' => '09211234567', 'email' => 'paolo.reyes@dti.gov.ph', 'address' => 'DTI Makati']);
        }

        // EcoWatt Solutions - Approved, needs coordinator. Verified + Active
        // for the same reason as founder@test.com above.
        $ecowattFounder = User::firstOrCreate(
            ['email' => 'ecowatt.founder@test.com'],
            ['name' => 'EcoWatt Founder', 'password' => 'password', 'role' => 'Startup']
        );
        $ecowattFounder->update(['account_status' => 'Active', 'email_verified_at' => now()]);

        $needsCoordinator = Startup::firstOrCreate(
            ['company_name' => 'EcoWatt Solutions'],
            [
                'user_id' => $ecowattFounder->id,
                'industry_sector' => 'CleanTech',
                'cohort_number' => 3,
                'contact_phone' => '09181234567',
                'location' => 'Taguig City, PH',
            ]
        );

        InformationSheet::firstOrCreate(
            ['startup_id' => $needsCoordinator->startup_id],
            ['approval_status' => 'Approved', 'business_description' => 'Placeholder']
        );

        if (TeamMember::where('startup_id', $needsCoordinator->startup_id)->count() === 0) {
            TeamMember::factory()->count(2)->create(['startup_id' => $needsCoordinator->startup_id]);
        }

        if (ReadinessLevelAssessment::where('startup_id', $needsCoordinator->startup_id)->count() === 0) {
            // Scores are derived from the seeded checkboxes via recomputeScores()
            // (below) rather than hardcoded, so they can never drift out of sync
            // with the progress JSON — and land on realistic decimals (partial
            // levels), not just round numbers.
            $assessment = new ReadinessLevelAssessment(array_merge([
                'startup_id' => $needsCoordinator->startup_id,
                'stage' => 'Pre-Assessment',
                'assessment_date' => now(),
            ], $this->rubricProgress(['TRL' => 6.3, 'MRL' => 4.0, 'TMRL' => 5.7, 'SRL' => 3.2])));
            $assessment->recomputeScores()->save();
        }

        // GreenLoop Energy - Pending sheet with an UPCOMING evaluation.
        // Deliberately NOT Approved: the Assessment Hub hides startups whose
        // Information Sheet is already approved from the Evaluation tab, so an
        // approved GreenLoop would never appear under "Upcoming". This is the
        // fully-filled-in sheet to open from Evaluation -> Upcoming -> View.
        $greenloopFounder = User::firstOrCreate(
            ['email' => 'greenloop.founder@test.com'],
            ['name' => 'GreenLoop Founder', 'password' => 'password', 'role' => 'Startup']
        );
        $greenloopFounder->update(['account_status' => 'Active', 'email_verified_at' => now()]);

        $greenloop = Startup::firstOrCreate(
            ['company_name' => 'GreenLoop Energy'],
            [
                'user_id' => $greenloopFounder->id,
                'industry_sector' => 'CleanTech',
                'cohort_number' => 3,
                'contact_phone' => '09191234567',
                'location' => 'Pasig City, PH',
            ]
        );
        $greenloop->update(['user_id' => $greenloopFounder->id]);

        $greenloopSheet = InformationSheet::firstOrCreate(
            ['startup_id' => $greenloop->startup_id],
            ['approval_status' => 'Pending', 'business_description' => 'Placeholder']
        );

        // Forced every run so an older seed (which created this sheet as
        // Approved) gets repaired instead of staying off the Upcoming list.
        $greenloopSheet->update([
            'approval_status' => 'Pending',
            'submission_date' => now()->subDays(21),
            'business_description' => 'GreenLoop Energy converts household and market food waste into biogas cartridges for off-grid cooking.',
            'target_market' => 'Off-grid and peri-urban households in Rizal and Laguna',
            'problem_statement' => 'Rural households spend a large share of income on LPG while wet market food waste goes unprocessed.',
            'solution_offered' => 'A community-scale digester plus a swap-and-refill cartridge network.',
            'surname' => 'Navarro', 'first_name' => 'Elias', 'middle_name' => 'Bautista',
            'name_extension' => 'N/A', 'height_m' => '1.72', 'weight_kg' => '68', 'blood_type' => 'B+',
            'gsis_no' => '2233445566', 'pagibig_no' => '2233-4455-6677', 'philhealth_no' => '22-334455667-8',
            'sss_no' => '22-3344556-7', 'residential_address' => '24 Kalayaan Ave., Pasig City',
            'permanent_address' => '24 Kalayaan Ave., Pasig City', 'sex' => 'Male',
            'civil_status' => 'Single', 'citizenship_by_birth' => 'Filipino', 'citizenship_dual' => 'N/A',
            'place_of_birth' => 'Pasig City', 'date_of_birth' => '1996-09-08', 'mobile_no' => '09191234567',
            'founder_email' => 'elias.navarro@greenloop.ph',
            'secondary_school' => 'Rizal High School', 'secondary_degree_course' => 'N/A',
            'secondary_highest_level_unit' => 'N/A', 'secondary_year_graduated' => '2012',
            'vocational_school' => 'N/A', 'vocational_degree_course' => 'N/A',
            'vocational_highest_level_unit' => 'N/A', 'vocational_year_graduated' => 'N/A',
            'college_school' => 'Polytechnic University of the Philippines',
            'college_degree_course' => 'BS Mechanical Engineering',
            'college_highest_level_unit' => "Bachelor's Degree", 'college_year_graduated' => '2017',
            'graduate_school' => 'N/A', 'graduate_degree_course' => 'N/A',
            'graduate_highest_level_unit' => 'N/A', 'graduate_year_graduated' => 'N/A',
            'scholarships_academic_honors' => "CHED Merit Scholar, 2013-2017\nDean's Lister, 2015-2017",
            'sec_registration' => 'CS201954321', 'business_id_number' => 'BID-0041237',
            'dti_registration_number' => 'DTI-0071188', 'business_tin' => '456-789-123-000',
            'non_academic_distinctions' => 'Finalist, DOST CleanTech Challenge 2024',
            'membership_associations' => 'Philippine Society of Mechanical Engineers',
            'date_accomplished' => now()->subDays(21)->toDateString(),
            'portfolio_manager' => 'Engr. Tristan Velardo',
            'cohort_no' => 'Cohort 3', 'endorsed_by' => 'Sir Erwin',
            'endorsement_date' => now()->subDays(20)->toDateString(),
        ]);

        if (TeamMember::where('startup_id', $greenloop->startup_id)->count() === 0) {
            TeamMember::create(['startup_id' => $greenloop->startup_id, 'full_name' => 'Elias Navarro', 'designation' => 'CEO', 'role' => 'CEO', 'phone' => '09191234567', 'address' => 'Pasig City', 'date_of_birth' => '1996-09-08', 'email' => 'elias@greenloop.ph', 'citizenship' => 'Filipino', 'sex' => 'Male', 'civil_status' => 'Single']);
            TeamMember::create(['startup_id' => $greenloop->startup_id, 'full_name' => 'Cathy Ramos', 'designation' => 'CTO', 'role' => 'CTO', 'phone' => '09192234567', 'address' => 'Cainta, Rizal', 'date_of_birth' => '1995-01-19', 'email' => 'cathy@greenloop.ph', 'citizenship' => 'Filipino', 'sex' => 'Female', 'civil_status' => 'Married']);
            TeamMember::create(['startup_id' => $greenloop->startup_id, 'full_name' => 'Mark Salazar', 'designation' => 'Field Operations Lead', 'role' => 'Operations', 'phone' => '09193234567', 'address' => 'Taytay, Rizal', 'date_of_birth' => '1998-06-27', 'email' => 'mark@greenloop.ph', 'citizenship' => 'Filipino', 'sex' => 'Male', 'civil_status' => 'Single']);
        }

        if (IncubationInvolvement::where('info_sheet_id', $greenloopSheet->info_sheet_id)->count() === 0) {
            IncubationInvolvement::create(['info_sheet_id' => $greenloopSheet->info_sheet_id, 'organization_name_address' => 'DOST-PCIEERD, Taguig', 'date_from' => '2024-02-01', 'date_to' => '2024-07-31', 'number_of_hours' => '100', 'incubation_program_focus' => 'CleanTech Prototyping']);
            IncubationInvolvement::create(['info_sheet_id' => $greenloopSheet->info_sheet_id, 'organization_name_address' => 'PUP-TBIDO, Sta. Mesa, Manila', 'date_from' => '2024-08-01', 'date_to' => '2025-01-31', 'number_of_hours' => '140', 'incubation_program_focus' => 'Market Validation']);
        }

        if (LdIntervention::where('info_sheet_id', $greenloopSheet->info_sheet_id)->count() === 0) {
            LdIntervention::create(['info_sheet_id' => $greenloopSheet->info_sheet_id, 'title' => 'Circular Economy Business Models', 'date_from' => '2024-05-06', 'date_to' => '2024-05-08', 'number_of_hours' => '24', 'conducted_sponsored_by' => 'DOST']);
            LdIntervention::create(['info_sheet_id' => $greenloopSheet->info_sheet_id, 'title' => 'Investor Readiness Workshop', 'date_from' => '2024-11-12', 'date_to' => '2024-11-13', 'number_of_hours' => '16', 'conducted_sponsored_by' => 'PUP-TBIDO']);
        }

        if (StartupReference::where('info_sheet_id', $greenloopSheet->info_sheet_id)->count() === 0) {
            StartupReference::create(['info_sheet_id' => $greenloopSheet->info_sheet_id, 'name' => 'Engr. Rowena Diaz', 'contact' => '09221234567', 'email' => 'rowena.diaz@pup.edu.ph', 'address' => 'PUP Sta. Mesa, Manila']);
            StartupReference::create(['info_sheet_id' => $greenloopSheet->info_sheet_id, 'name' => 'Mr. Alfonso Yu', 'contact' => '09231234567', 'email' => 'alfonso.yu@dost.gov.ph', 'address' => 'DOST Taguig']);
        }

        if (ReadinessLevelAssessment::where('startup_id', $greenloop->startup_id)->count() === 0) {
            $assessment = new ReadinessLevelAssessment(array_merge([
                'startup_id' => $greenloop->startup_id,
                'stage' => 'Pre-Assessment',
                'assessment_date' => now(),
            ], $this->rubricProgress(['TRL' => 6.0, 'MRL' => 5.4, 'TMRL' => 4.6, 'SRL' => 4.1])));
            $assessment->recomputeScores()->save();
        }

        // The upcoming evaluation itself — always pushed to a future date so
        // re-seeding an old database still lands GreenLoop under "Upcoming"
        // rather than "Missed".
        $greenloopEvaluation = EvaluationSchedule::firstOrNew(['startup_id' => $greenloop->startup_id]);
        $greenloopEvaluation->fill([
            'evaluation_date' => now()->addDays(7)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'Scheduled',
            'notes' => 'Initial evaluation for Cohort 3 endorsement.',
        ])->save();

        // Sample mentors
        Mentor::firstOrCreate(
            ['contact_email' => 'cruz@gmail.com'],
            ['honorific' => 'Ms.', 'first_name' => 'Jennie', 'last_name' => 'Cruz', 'full_name' => 'Ms. Jennie Cruz', 'specialization' => 'Engineering', 'contact_number' => '09562549512']
        );

        Mentor::firstOrCreate(
            ['contact_email' => 'itsargeebueno@gmail.com'],
            ['honorific' => 'Mr.', 'first_name' => 'Argee', 'last_name' => 'Bueno', 'full_name' => 'Mr. Argee Bueno', 'specialization' => 'Business', 'contact_number' => '09695641213']
        );

        // Sample coordinators
        Coordinator::firstOrCreate(
            ['email' => 'jennie@pup.edu.ph'],
            ['honorific' => "Ma'am", 'first_name' => 'Jennie', 'last_name' => 'Kim', 'name' => "Ma'am Jennie Kim", 'role_title' => 'Portfolio Coordinator', 'phone' => '09562549512']
        );

        Coordinator::firstOrCreate(
            ['email' => 'tristan@pup.edu.ph'],
            ['honorific' => 'Sir', 'first_name' => 'Tristan', 'last_name' => 'Velardo', 'name' => 'Sir Tristan Velardo', 'role_title' => 'Portfolio Coordinator', 'phone' => '09562549512']
        );

        $this->command->info('Dev data seeded successfully.');
        $this->command->info('Ready-to-login accounts (password: "password"):');
        $this->command->info('  Admin:   admin@pup.edu.ph');
        $this->command->info('  Founder: founder@test.com (AgriSense PH), ecowatt.founder@test.com (EcoWatt Solutions), greenloop.founder@test.com (GreenLoop Energy — Pending sheet + upcoming evaluation)');
        $this->command->info('For Pending/Rejected/unverified test accounts (to try the admin approval and verify-email screens), run: php artisan db:seed --class=FounderApplicationSeeder');
    }

    /**
     * Builds the *_progress JSON that matches a seeded (decimal) score, so
     * the rubric checkboxes agree with the badge instead of showing "6.3/9"
     * over an untouched form. A score is the SUM, across all 9 levels, of
     * (checked criteria ÷ total criteria) per level (see
     * ReadinessRubric::scoreFromProgress) — so levels below the whole part
     * get every criterion ticked, the next level gets just enough criteria
     * ticked to contribute its fractional remainder, and the rest stay
     * untouched. The resulting score (recomputed via recomputeScores() by
     * the caller) may land a little off the requested value where the
     * fraction can't be hit exactly with a whole number of criteria — fine
     * for seed/test data, which only needs to look like a real partially
     * filled-in assessment, not reproduce an exact number.
     *
     * @param  array<string,float>  $scores  e.g. ['TRL' => 6.3, 'MRL' => 4.0, ...]
     * @return array<string,array<int,array<int,bool>>>
     */
    private function rubricProgress(array $scores): array
    {
        $progress = [];

        foreach ($scores as $type => $score) {
            $levels = [];
            $whole = (int) floor($score);
            $remainder = $score - $whole;

            foreach (ReadinessRubric::levels($type) as $level => $definition) {
                $criteriaCount = count($definition['criteria']);

                if ($level <= $whole) {
                    $levels[$level] = array_fill(0, $criteriaCount, true);
                } elseif ($level === $whole + 1 && $remainder > 0) {
                    $toCheck = max(1, min($criteriaCount, (int) round($remainder * $criteriaCount)));
                    $levels[$level] = array_merge(
                        array_fill(0, $toCheck, true),
                        array_fill(0, $criteriaCount - $toCheck, false)
                    );
                } else {
                    $levels[$level] = array_fill(0, $criteriaCount, false);
                }
            }

            $progress[strtolower($type).'_progress'] = $levels;
        }

        return $progress;
    }
}

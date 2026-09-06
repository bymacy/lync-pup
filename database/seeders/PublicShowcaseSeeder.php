<?php

namespace Database\Seeders;

use App\Models\AssessmentDocument;
use App\Models\Cohort;
use App\Models\InformationSheet;
use App\Models\ReadinessLevelAssessment;
use App\Models\Startup;
use App\Models\StartupTeamMember;
use App\Models\User;
use App\Support\ReadinessRubric;
use App\Support\VentureExitForm;
use Illuminate\Database\Seeder;

/**
 * Populates a handful of fully-Approved startups (with cohorts, team
 * rosters, and Pre/Post readiness scores) purely so the public landing
 * page (WelcomeController -> resources/views/welcome.blade.php) has real
 * data to show instead of "No approved startups to show yet." Safe to
 * re-run: everything is firstOrCreate'd against a unique company name.
 */
class PublicShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $activeCohort = Cohort::firstOrCreate(
            ['number' => 5],
            ['label' => 'Cohort 5', 'status' => 'Active', 'start_date' => now()->subMonths(4), 'end_date' => now()->addMonths(2), 'description' => 'Current incubation batch.']
        );

        $graduatedCohort = Cohort::firstOrCreate(
            ['number' => 2],
            ['label' => 'Cohort 2', 'status' => 'Inactive', 'start_date' => now()->subYear(), 'end_date' => now()->subMonths(6), 'description' => 'Completed incubation batch.']
        );

        // Company, industry, cohort, location, website, description, team,
        // Pre-Assessment scores, Post-Assessment scores (null = not reached
        // that stage yet), graduated (Venture Exit form on file).
        $startups = [
            [
                'company' => 'AquaPure Filtration',
                'email' => 'aquapure.founder@showcase.test',
                'founder' => 'Isabel Ramos',
                'industry' => 'CleanTech',
                'cohort' => $activeCohort,
                'location' => 'Quezon City, PH',
                'website' => 'https://aquapure.example.com',
                'description' => 'Low-cost ceramic water filtration units for off-grid barangays, manufactured locally from reclaimed clay.',
                'team' => ['Isabel Ramos', 'Miguel Santos', 'Faye Villareal'],
                'pre' => ['TRL' => 6.5, 'MRL' => 5.2, 'TMRL' => 6.0, 'SRL' => 4.8],
                'post' => ['TRL' => 7.4, 'MRL' => 6.6, 'TMRL' => 6.8, 'SRL' => 5.9],
                'graduated' => false,
            ],
            [
                'company' => 'SolarNest Energy',
                'email' => 'solarnest.founder@showcase.test',
                'founder' => 'Daniel Cruz',
                'industry' => 'Renewable Energy',
                'cohort' => $graduatedCohort,
                'location' => 'Batangas City, PH',
                'website' => 'https://solarnest.example.com',
                'description' => 'Modular solar microgrid kits for small poultry and hog farms, cutting diesel generator costs by up to 60%.',
                'team' => ['Daniel Cruz', 'Angela Reyes'],
                'pre' => ['TRL' => 7.0, 'MRL' => 6.4, 'TMRL' => 7.1, 'SRL' => 6.0],
                'post' => ['TRL' => 8.2, 'MRL' => 7.8, 'TMRL' => 8.0, 'SRL' => 7.5],
                'graduated' => true,
            ],
            [
                'company' => 'CodeCraft Analytics',
                'email' => 'codecraft.founder@showcase.test',
                'founder' => 'Patricia Nolasco',
                'industry' => 'FinTech',
                'cohort' => $activeCohort,
                'location' => 'Makati City, PH',
                'website' => 'https://codecraft.example.com',
                'description' => 'A no-code analytics dashboard that helps sari-sari store cooperatives track daily sales and inventory.',
                'team' => ['Patricia Nolasco', 'Jerome Aquino', 'Ken Villanueva'],
                'pre' => ['TRL' => 5.6, 'MRL' => 4.4, 'TMRL' => 5.9, 'SRL' => 4.2],
                'post' => null,
                'graduated' => false,
            ],
            [
                'company' => 'HarvestLink AgriTech',
                'email' => 'harvestlink.founder@showcase.test',
                'founder' => 'Ramon Dizon',
                'industry' => 'AgriTech',
                'cohort' => $activeCohort,
                'location' => 'Nueva Ecija, PH',
                'website' => 'https://harvestlink.example.com',
                'description' => 'A logistics-matching app connecting smallholder rice farmers directly to wholesale buyers, cutting out middlemen.',
                'team' => ['Ramon Dizon', 'Cristina Fajardo'],
                'pre' => ['TRL' => 6.1, 'MRL' => 5.0, 'TMRL' => 5.5, 'SRL' => 5.3],
                'post' => ['TRL' => 6.9, 'MRL' => 5.8, 'TMRL' => 6.4, 'SRL' => 6.1],
                'graduated' => false,
            ],
            [
                'company' => 'MediTrack Systems',
                'email' => 'meditrack.founder@showcase.test',
                'founder' => 'Sophia Garcia',
                'industry' => 'HealthTech',
                'cohort' => $activeCohort,
                'location' => 'Cebu City, PH',
                'website' => 'https://meditrack.example.com',
                'description' => 'A lightweight patient queueing and records app for rural health units with intermittent internet access.',
                'team' => ['Sophia Garcia', 'Nathan Uy'],
                'pre' => ['TRL' => 5.9, 'MRL' => 4.7, 'TMRL' => 5.4, 'SRL' => 4.6],
                'post' => null,
                'graduated' => false,
            ],
            [
                'company' => 'EcoWeave Textiles',
                'email' => 'ecoweave.founder@showcase.test',
                'founder' => 'Louie Manalo',
                'industry' => 'Sustainable Manufacturing',
                'cohort' => $activeCohort,
                'location' => 'Iloilo City, PH',
                'website' => 'https://ecoweave.example.com',
                'description' => 'Woven textiles made from banana and pineapple fiber waste, sold to local fashion cooperatives.',
                'team' => ['Louie Manalo', 'Grace Ledesma', 'Tomas Bautista'],
                'pre' => ['TRL' => 6.3, 'MRL' => 5.5, 'TMRL' => 5.1, 'SRL' => 4.9],
                'post' => ['TRL' => 7.0, 'MRL' => 6.2, 'TMRL' => 5.8, 'SRL' => 5.7],
                'graduated' => false,
            ],
            [
                'company' => 'SkyRoute Logistics',
                'email' => 'skyroute.founder@showcase.test',
                'founder' => 'Bianca Torres',
                'industry' => 'Logistics',
                'cohort' => $activeCohort,
                'location' => 'Davao City, PH',
                'website' => 'https://skyroute.example.com',
                'description' => 'Drone-assisted last-mile delivery routing for island and mountain barangays underserved by couriers.',
                'team' => ['Bianca Torres', 'Enzo Ramirez'],
                'pre' => ['TRL' => 4.8, 'MRL' => 4.0, 'TMRL' => 5.2, 'SRL' => 3.9],
                'post' => null,
                'graduated' => false,
            ],
            [
                'company' => 'BrightPath Learning',
                'email' => 'brightpath.founder@showcase.test',
                'founder' => 'Kevin Domingo',
                'industry' => 'EdTech',
                'cohort' => $activeCohort,
                'location' => 'Baguio City, PH',
                'website' => 'https://brightpath.example.com',
                'description' => 'Offline-first tutoring modules for public school learners in areas with limited data connectivity.',
                'team' => ['Kevin Domingo', 'Aira Cabrera', 'Nico Salonga'],
                'pre' => ['TRL' => 6.7, 'MRL' => 5.3, 'TMRL' => 6.5, 'SRL' => 5.5],
                'post' => ['TRL' => 7.2, 'MRL' => 6.0, 'TMRL' => 7.0, 'SRL' => 6.3],
                'graduated' => false,
            ],
        ];

        foreach ($startups as $entry) {
            $this->makeStartup($entry);
        }

        $this->command->info('Public showcase startups seeded — visit / to view the landing page.');
    }

    private function makeStartup(array $entry): void
    {
        $founder = User::firstOrCreate(
            ['email' => $entry['email']],
            ['name' => $entry['founder'], 'password' => 'password', 'role' => 'Startup']
        );
        $founder->update(['account_status' => 'Active', 'email_verified_at' => now()]);

        $startup = Startup::firstOrCreate(
            ['company_name' => $entry['company']],
            [
                'user_id' => $founder->id,
                'industry_sector' => $entry['industry'],
                'cohort_id' => $entry['cohort']->cohort_id,
                'cohort_number' => $entry['cohort']->number,
                'business_description' => $entry['description'],
                'contact_phone' => '09' . random_int(100000000, 999999999),
                'location' => $entry['location'],
                'website' => $entry['website'],
            ]
        );
        $startup->update([
            'user_id' => $founder->id,
            'cohort_id' => $entry['cohort']->cohort_id,
            'business_description' => $entry['description'],
        ]);

        InformationSheet::firstOrCreate(
            ['startup_id' => $startup->startup_id],
            [
                'approval_status' => 'Approved',
                'business_description' => $entry['description'],
                'submission_date' => now()->subDays(60),
            ]
        );

        if (StartupTeamMember::where('startup_id', $startup->startup_id)->count() === 0) {
            foreach ($entry['team'] as $name) {
                StartupTeamMember::create(['startup_id' => $startup->startup_id, 'full_name' => $name]);
            }
        }

        $this->makeAssessment($startup, 'Pre-Assessment', $entry['pre']);

        if ($entry['post']) {
            $this->makeAssessment($startup, 'Post-Assessment', $entry['post']);
        }

        if ($entry['graduated']) {
            AssessmentDocument::firstOrCreate(
                ['startup_id' => $startup->startup_id, 'stage' => 'Venture Exit', 'document_number' => VentureExitForm::DOCUMENT_NUMBER],
                ['data' => ['graduation_readiness' => array_fill_keys(VentureExitForm::GRADUATION_READINESS_INDICATORS, true)]]
            );
        }
    }

    private function makeAssessment(Startup $startup, string $stage, array $scores): void
    {
        if (ReadinessLevelAssessment::where('startup_id', $startup->startup_id)->where('stage', $stage)->exists()) {
            return;
        }

        $assessment = new ReadinessLevelAssessment(array_merge([
            'startup_id' => $startup->startup_id,
            'stage' => $stage,
            'assessment_date' => now(),
        ], $this->rubricProgress($scores)));
        $assessment->recomputeScores()->save();
    }

    /**
     * Same helper as DevDataSeeder::rubricProgress() — builds the *_progress
     * checkbox JSON that a requested decimal score derives from, so the
     * seeded rubric checkboxes agree with the badge shown everywhere else.
     *
     * @param  array<string,float>  $scores
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

            $progress[strtolower($type) . '_progress'] = $levels;
        }

        return $progress;
    }
}

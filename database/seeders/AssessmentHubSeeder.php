<?php

namespace Database\Seeders;

use App\Models\EvaluationSchedule;
use App\Models\InformationSheet;
use App\Models\Startup;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssessmentHubSeeder extends Seeder
{
    public function run(): void
    {
        // Make sure the baseline dev data exists too (AgriSense PH = Pending/
        // Not Started, EcoWatt Solutions = Approved). Safe to call repeatedly —
        // everything in it is firstOrCreate.
        $this->call(DevDataSeeder::class);

        // ---------------------------------------------------------------
        // GreenByte Innovations — evaluation scheduled for TODAY
        // ---------------------------------------------------------------
        $this->makeStartup(
            company: 'GreenByte Innovations',
            email: 'greenbyte.founder@test.com',
            founderName: 'Ramon Aquino',
            industry: 'CleanTech',
            cohort: 4,
            evaluationDate: now(),
            startTime: '10:00',
            endTime: '11:00',
            status: 'Scheduled',
        );

        // ---------------------------------------------------------------
        // UrbanFarm Tech — evaluation scheduled a few days from now
        // ---------------------------------------------------------------
        $this->makeStartup(
            company: 'UrbanFarm Tech',
            email: 'urbanfarm.founder@test.com',
            founderName: 'Bea Lim',
            industry: 'AgriTech',
            cohort: 4,
            evaluationDate: now()->addDays(5),
            startTime: '09:00',
            endTime: '10:00',
            status: 'Scheduled',
        );

        // ---------------------------------------------------------------
        // NutriPack Solutions — another upcoming evaluation, same month as
        // UrbanFarm Tech (so the "Upcoming" month filter has 2+ rows to group)
        // ---------------------------------------------------------------
        $this->makeStartup(
            company: 'NutriPack Solutions',
            email: 'nutripack.founder@test.com',
            founderName: 'Carlo Mendoza',
            industry: 'FoodTech',
            cohort: 4,
            evaluationDate: now()->addDays(10),
            startTime: '13:00',
            endTime: '14:00',
            status: 'Scheduled',
        );

        // ---------------------------------------------------------------
        // SkillBridge PH — upcoming evaluation further out, likely a
        // different month (so the month filter dropdown has 2+ options)
        // ---------------------------------------------------------------
        $this->makeStartup(
            company: 'SkillBridge PH',
            email: 'skillbridge.founder@test.com',
            founderName: 'Nina Torres',
            industry: 'EdTech',
            cohort: 4,
            evaluationDate: now()->addDays(25),
            startTime: '14:00',
            endTime: '15:00',
            status: 'Scheduled',
        );

        // ---------------------------------------------------------------
        // MedConnect Solutions — evaluation date already passed but was
        // never marked Completed, so it shows up under "Missed"
        // ---------------------------------------------------------------
        $this->makeStartup(
            company: 'MedConnect Solutions',
            email: 'medconnect.founder@test.com',
            founderName: 'Diane Ocampo',
            industry: 'HealthTech',
            cohort: 3,
            evaluationDate: now()->subDays(10),
            startTime: '11:00',
            endTime: '12:00',
            status: 'Scheduled',
        );

        // ---------------------------------------------------------------
        // EduSpark Learning — already Approved (2nd row on the Approved
        // tab) with a Completed evaluation in its history
        // ---------------------------------------------------------------
        $this->makeStartup(
            company: 'EduSpark Learning',
            email: 'eduspark.founder@test.com',
            founderName: 'Patrick Villanueva',
            industry: 'EdTech',
            cohort: 3,
            evaluationDate: now()->subDays(20),
            startTime: '08:00',
            endTime: '09:00',
            status: 'Completed',
            approvalStatus: 'Approved',
        );

        $this->command->info('Assessment Hub sample data seeded successfully.');
    }

    private function makeStartup(
        string $company,
        string $email,
        string $founderName,
        string $industry,
        int $cohort,
        $evaluationDate,
        string $startTime,
        string $endTime,
        string $status,
        string $approvalStatus = 'Pending',
    ): void {
        $founder = User::firstOrCreate(
            ['email' => $email],
            ['name' => $founderName, 'password' => 'password', 'role' => 'Startup']
        );

        $startup = Startup::firstOrCreate(
            ['company_name' => $company],
            [
                'user_id' => $founder->id,
                'industry_sector' => $industry,
                'cohort_number' => $cohort,
                'contact_phone' => '0917' . random_int(1000000, 9999999),
                'location' => 'Metro Manila, PH',
            ]
        );

        InformationSheet::firstOrCreate(
            ['startup_id' => $startup->startup_id],
            [
                'business_description' => "{$company} is a sample startup seeded for Assessment Hub preview data.",
                'submission_date' => now()->subDays(30),
                'approval_status' => $approvalStatus,
            ]
        );

        EvaluationSchedule::firstOrCreate(
            [
                'startup_id' => $startup->startup_id,
                'evaluation_date' => $evaluationDate->toDateString(),
            ],
            [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => $status,
                'notes' => null,
            ]
        );
    }
}

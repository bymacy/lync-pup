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

        // Founder test account
        $founder = User::firstOrCreate(
            ['email' => 'founder@test.com'],
            ['name' => 'Maria Santos', 'password' => 'password', 'role' => 'Startup']
        );

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

        // EcoWatt Solutions - Approved, needs coordinator
        $ecowattFounder = User::firstOrCreate(
            ['email' => 'ecowatt.founder@test.com'],
            ['name' => 'EcoWatt Founder', 'password' => 'password', 'role' => 'Startup']
        );

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
            ReadinessLevelAssessment::create([
                'startup_id' => $needsCoordinator->startup_id,
                'trl_score' => 6, 'mrl_score' => 4, 'tmrl_score' => 5, 'srl_score' => 3,
                'overall_score' => 4.5, 'assessment_date' => now(),
            ]);
        }

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
    }
}
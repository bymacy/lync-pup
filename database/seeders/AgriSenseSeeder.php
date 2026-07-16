<?php

namespace Database\Seeders;

use App\Models\InformationSheet;
use App\Models\TeamMember;
use App\Models\IncubationInvolvement;
use App\Models\LdIntervention;
use App\Models\StartupReference;
use Illuminate\Database\Seeder;

class AgriSenseSeeder extends Seeder
{
    public function run(): void
    {
        $sheet = InformationSheet::where('startup_id', 1)->first();

        if (! $sheet) {
            $this->command->error('No information sheet found for startup_id 1.');
            return;
        }

        $sheet->update([
            'surname' => 'Santos',
            'first_name' => 'Maria',
            'middle_name' => 'Reyes',
            'name_extension' => 'N/A',
            'height_m' => '1.65',
            'weight_kg' => '58',
            'blood_type' => 'O+',
            'gsis_no' => '1234567890',
            'pagibig_no' => '1234-5678-9012',
            'philhealth_no' => '12-345678901-2',
            'sss_no' => '12-3456789-0',
            'residential_address' => 'B11 L3 Sample St., Mandaluyong City',
            'permanent_address' => 'B11 L3 Sample St., Mandaluyong City',
            'sex' => 'Female',
            'civil_status' => 'Single',
            'citizenship_by_birth' => 'Filipino',
            'citizenship_dual' => 'N/A',
            'place_of_birth' => 'Manila',
            'date_of_birth' => '1998-05-14',
            'mobile_no' => '09171234567',
            'founder_email' => 'maria.santos@agrisense.ph',
            'secondary_school' => 'Manila High School',
            'secondary_degree_course' => 'N/A',
            'secondary_highest_level_unit' => 'N/A',
            'secondary_year_graduated' => '2014',
            'vocational_school' => 'N/A',
            'vocational_degree_course' => 'N/A',
            'vocational_highest_level_unit' => 'N/A',
            'vocational_year_graduated' => 'N/A',
            'college_school' => 'Polytechnic University of the Philippines',
            'college_degree_course' => 'BS Computer Science',
            'college_highest_level_unit' => "Bachelor's Degree",
            'college_year_graduated' => '2018',
            'graduate_school' => 'Polytechnic University of the Philippines',
            'graduate_degree_course' => 'Master in Business Administration',
            'graduate_highest_level_unit' => "Master's Degree",
            'graduate_year_graduated' => '2021',
            'scholarships_academic_honors' => "Dean's Lister, 2016-2018\nDOST Scholarship Grantee",
            'sec_registration' => 'CS201812345',
            'business_id_number' => 'BID-0098765',
            'dti_registration_number' => 'DTI-0054321',
            'business_tin' => '123-456-789-000',
            'non_academic_distinctions' => 'Best Startup Pitch, PUP Innovation Summit 2023',
            'membership_associations' => 'Philippine Startup Founders Network',
            'date_accomplished' => '2026-07-10',
            'portfolio_manager' => 'Engr. Tristan Velardo',
            'cohort_no' => 'Cohort 3',
            'endorsed_by' => 'Sir Erwin',
            'endorsement_date' => '2026-07-11',
        ]);
        // Clear any previous test rows first, so re-running this seeder doesn't duplicate
        TeamMember::where('startup_id', 1)->delete();
        IncubationInvolvement::where('info_sheet_id', $sheet->info_sheet_id)->delete();
        LdIntervention::where('info_sheet_id', $sheet->info_sheet_id)->delete();
        StartupReference::where('info_sheet_id', $sheet->info_sheet_id)->delete();

        // Team members (multiple rows)
        TeamMember::create([
            'startup_id' => 1, 'full_name' => 'Maria Santos', 'designation' => 'CEO', 'role' => 'CEO',
            'phone' => '09171234567', 'address' => 'Mandaluyong City', 'date_of_birth' => '1998-05-14',
            'email' => 'maria@agrisense.ph', 'citizenship' => 'Filipino', 'sex' => 'Female', 'civil_status' => 'Single',
        ]);
        TeamMember::create([
            'startup_id' => 1, 'full_name' => 'Juan Dela Cruz', 'designation' => 'CTO', 'role' => 'CTO',
            'phone' => '09181234567', 'address' => 'Quezon City', 'date_of_birth' => '1997-03-22',
            'email' => 'juan@agrisense.ph', 'citizenship' => 'Filipino', 'sex' => 'Male', 'civil_status' => 'Single',
        ]);
        TeamMember::create([
            'startup_id' => 1, 'full_name' => 'Liza Tan', 'designation' => 'Operations Lead', 'role' => 'Operations',
            'phone' => '09191234567', 'address' => 'Pasig City', 'date_of_birth' => '1999-11-02',
            'email' => 'liza@agrisense.ph', 'citizenship' => 'Filipino', 'sex' => 'Female', 'civil_status' => 'Single',
        ]);

        // Incubation involvement (multiple rows)
        IncubationInvolvement::create([
            'info_sheet_id' => $sheet->info_sheet_id, 'organization_name_address' => 'DTI Negosyo Center, Manila',
            'date_from' => '2023-01-01', 'date_to' => '2023-06-30', 'number_of_hours' => '80',
            'incubation_program_focus' => 'Business Development',
        ]);
        IncubationInvolvement::create([
            'info_sheet_id' => $sheet->info_sheet_id, 'organization_name_address' => 'QBO Innovation Hub, Makati',
            'date_from' => '2023-07-01', 'date_to' => '2023-12-15', 'number_of_hours' => '120',
            'incubation_program_focus' => 'Tech Acceleration',
        ]);

        // L&D interventions (multiple rows)
        LdIntervention::create([
            'info_sheet_id' => $sheet->info_sheet_id, 'title' => 'Pitch Deck Bootcamp',
            'date_from' => '2023-08-01', 'date_to' => '2023-08-03', 'number_of_hours' => '24',
            'conducted_sponsored_by' => 'PUP-TBIDO',
        ]);
        LdIntervention::create([
            'info_sheet_id' => $sheet->info_sheet_id, 'title' => 'Financial Literacy for Startups',
            'date_from' => '2023-09-10', 'date_to' => '2023-09-11', 'number_of_hours' => '16',
            'conducted_sponsored_by' => 'DTI',
        ]);

        // References (multiple rows)
        StartupReference::create([
            'info_sheet_id' => $sheet->info_sheet_id, 'name' => 'Dr. Ana Cruz', 'contact' => '09201234567',
            'email' => 'ana.cruz@pup.edu.ph', 'address' => 'PUP Sta. Mesa, Manila',
        ]);
        StartupReference::create([
            'info_sheet_id' => $sheet->info_sheet_id, 'name' => 'Engr. Paolo Reyes', 'contact' => '09211234567',
            'email' => 'paolo.reyes@dti.gov.ph', 'address' => 'DTI Makati',
        ]);

        $this->command->info('AgriSense PH information sheet updated successfully.');
    }
}
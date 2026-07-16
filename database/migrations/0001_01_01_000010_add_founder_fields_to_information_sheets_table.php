<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('information_sheets', function (Blueprint $table) {
            // I. Founder's Information
            $table->string('surname', 100)->nullable()->after('startup_id');
            $table->string('first_name', 100)->nullable();
            $table->string('middle_name', 100)->nullable();
            $table->string('name_extension', 20)->nullable();
            $table->string('height_m', 20)->nullable();
            $table->string('weight_kg', 20)->nullable();
            $table->string('blood_type', 10)->nullable();
            $table->string('gsis_no', 50)->nullable();
            $table->string('pagibig_no', 50)->nullable();
            $table->string('philhealth_no', 50)->nullable();
            $table->string('sss_no', 50)->nullable();
            $table->string('residential_address', 255)->nullable();
            $table->string('permanent_address', 255)->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('civil_status', 30)->nullable();
            $table->string('citizenship_by_birth', 100)->nullable();
            $table->string('citizenship_dual', 100)->nullable();
            $table->string('place_of_birth', 150)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('mobile_no', 20)->nullable();
            $table->string('founder_email', 150)->nullable();

            // 22. Educational Background (fixed 4 levels)
            foreach (['secondary', 'vocational', 'college', 'graduate'] as $level) {
                $table->string("{$level}_school", 150)->nullable();
                $table->string("{$level}_degree_course", 150)->nullable();
                $table->string("{$level}_highest_level_unit", 100)->nullable();
                $table->string("{$level}_year_graduated", 10)->nullable();
            }

            // 23. Scholarships / Academic Honors (simple repeatable text list)
            $table->text('scholarships_academic_honors')->nullable();

            // V. Startup Information (additional fields)
            $table->string('sec_registration', 100)->nullable();
            $table->string('business_id_number', 100)->nullable();
            $table->string('dti_registration_number', 100)->nullable();
            $table->string('business_tin', 100)->nullable();
            $table->text('non_academic_distinctions')->nullable();
            $table->text('membership_associations')->nullable();

            // 36. Declaration & Endorsement
            $table->string('founder_signature_path')->nullable();
            $table->date('date_accomplished')->nullable();
            $table->string('portfolio_manager', 150)->nullable();
            $table->string('cohort_no', 20)->nullable();
            $table->string('endorsed_by', 150)->nullable();
            $table->date('endorsement_date')->nullable();
            $table->string('director_signature_path')->nullable();
            $table->date('director_approval_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('information_sheets', function (Blueprint $table) {
            $table->dropColumn([
                'surname', 'first_name', 'middle_name', 'name_extension', 'height_m', 'weight_kg',
                'blood_type', 'gsis_no', 'pagibig_no', 'philhealth_no', 'sss_no',
                'residential_address', 'permanent_address', 'sex', 'civil_status',
                'citizenship_by_birth', 'citizenship_dual', 'place_of_birth', 'date_of_birth',
                'mobile_no', 'founder_email',
                'secondary_school', 'secondary_degree_course', 'secondary_highest_level_unit', 'secondary_year_graduated',
                'vocational_school', 'vocational_degree_course', 'vocational_highest_level_unit', 'vocational_year_graduated',
                'college_school', 'college_degree_course', 'college_highest_level_unit', 'college_year_graduated',
                'graduate_school', 'graduate_degree_course', 'graduate_highest_level_unit', 'graduate_year_graduated',
                'scholarships_academic_honors',
                'sec_registration', 'business_id_number', 'dti_registration_number', 'business_tin',
                'non_academic_distinctions', 'membership_associations',
                'founder_signature_path', 'date_accomplished', 'portfolio_manager', 'cohort_no',
                'endorsed_by', 'endorsement_date', 'director_signature_path', 'director_approval_date',
            ]);
        });
    }
};
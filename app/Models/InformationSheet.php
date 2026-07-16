<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformationSheet extends Model
{
    use HasFactory;

    protected $primaryKey = 'info_sheet_id';

    protected $fillable = [
        'startup_id', 'business_description', 'target_market', 'problem_statement', 'solution_offered',
        'submission_date', 'approval_status', 'evaluator_remarks',
        'surname', 'first_name', 'middle_name', 'name_extension', 'height_m', 'weight_kg', 'blood_type',
        'gsis_no', 'pagibig_no', 'philhealth_no', 'sss_no', 'residential_address', 'permanent_address',
        'sex', 'civil_status', 'citizenship_by_birth', 'citizenship_dual', 'place_of_birth', 'date_of_birth',
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
    ];

    protected function casts(): array
    {
        return [
            'submission_date' => 'date',
            'date_of_birth' => 'date',
            'date_accomplished' => 'date',
            'endorsement_date' => 'date',
            'director_approval_date' => 'date',
        ];
    }

    public function startup()
    {
        return $this->belongsTo(Startup::class, 'startup_id');
    }

    public function incubationInvolvements()
    {
        return $this->hasMany(IncubationInvolvement::class, 'info_sheet_id');
    }

    public function ldInterventions()
    {
        return $this->hasMany(LdIntervention::class, 'info_sheet_id');
    }

    public function references()
    {
        return $this->hasMany(StartupReference::class, 'info_sheet_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->surname} {$this->name_extension}");
    }
}
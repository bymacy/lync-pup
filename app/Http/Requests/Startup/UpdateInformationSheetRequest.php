<?php

namespace App\Http\Requests\Startup;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInformationSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStartup();
    }

    protected function prepareForValidation(): void
    {
        if ($this->hasFile('founder_signature') && ! $this->file('founder_signature')->isValid()) {
            $this->files->remove('founder_signature');
        }
    }

    public function rules(): array
    {
        return [
            'surname' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'name_extension' => ['nullable', 'string', 'max:20'],
            'height_m' => ['nullable', 'string', 'max:20'],
            'weight_kg' => ['nullable', 'string', 'max:20'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'gsis_no' => ['nullable', 'string', 'max:50'],
            'pagibig_no' => ['nullable', 'string', 'max:50'],
            'philhealth_no' => ['nullable', 'string', 'max:50'],
            'sss_no' => ['nullable', 'string', 'max:50'],
            'residential_address' => ['nullable', 'string', 'max:255'],
            'permanent_address' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', 'string', 'max:20'],
            'civil_status' => ['nullable', 'string', 'max:30'],
            'citizenship_by_birth' => ['nullable', 'string', 'max:100'],
            'citizenship_dual' => ['nullable', 'string', 'max:100'],
            'place_of_birth' => ['nullable', 'string', 'max:150'],
            'date_of_birth' => ['nullable', 'date'],
            'mobile_no' => ['nullable', 'string', 'max:20'],
            'founder_email' => ['nullable', 'email', 'max:150'],

            'secondary_school' => ['nullable', 'string', 'max:150'],
            'secondary_degree_course' => ['nullable', 'string', 'max:150'],
            'secondary_highest_level_unit' => ['nullable', 'string', 'max:100'],
            'secondary_year_graduated' => ['nullable', 'string', 'max:10'],
            'vocational_school' => ['nullable', 'string', 'max:150'],
            'vocational_degree_course' => ['nullable', 'string', 'max:150'],
            'vocational_highest_level_unit' => ['nullable', 'string', 'max:100'],
            'vocational_year_graduated' => ['nullable', 'string', 'max:10'],
            'college_school' => ['nullable', 'string', 'max:150'],
            'college_degree_course' => ['nullable', 'string', 'max:150'],
            'college_highest_level_unit' => ['nullable', 'string', 'max:100'],
            'college_year_graduated' => ['nullable', 'string', 'max:10'],
            'graduate_school' => ['nullable', 'string', 'max:150'],
            'graduate_degree_course' => ['nullable', 'string', 'max:150'],
            'graduate_highest_level_unit' => ['nullable', 'string', 'max:100'],
            'graduate_year_graduated' => ['nullable', 'string', 'max:10'],
            'scholarships_academic_honors' => ['nullable', 'string'],

            'sec_registration' => ['nullable', 'string', 'max:100'],
            'business_id_number' => ['nullable', 'string', 'max:100'],
            'dti_registration_number' => ['nullable', 'string', 'max:100'],
            'business_tin' => ['nullable', 'string', 'max:100'],
            'non_academic_distinctions' => ['nullable', 'string'],
            'membership_associations' => ['nullable', 'string'],

            'date_accomplished' => ['nullable', 'date'],
            'founder_signature' => ['nullable', 'image', 'max:20480'],
        ];
    }
}
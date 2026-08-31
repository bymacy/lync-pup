<?php

namespace App\Http\Requests\Startup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStartupProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStartup();
    }

    protected function prepareForValidation(): void
    {
        // A failed upload (PHP-level size limit, interrupted POST) arrives as
        // an invalid UploadedFile. It is dropped here so it can't reach the
        // 'image' rule with a confusing message — but it must NOT pass
        // silently, since the photo is part of what makes a profile complete,
        // so withValidator() below turns it into a real error.
        if ($this->hasFile('startup_photo') && ! $this->file('startup_photo')->isValid()) {
            $this->files->remove('startup_photo');
            $this->merge(['startup_photo_upload_failed' => true]);
        }
    }

    /**
     * Every field Startup::isProfileComplete() checks is required here, so a
     * profile can never save "successfully" and still count as incomplete —
     * that mismatch is what left founders staring at the dashboard's
     * "Action Required: Startup Profile" banner with no field to fix.
     *
     * startup_photo is the one exception to a plain 'required': it is a file
     * input, so it is only demanded when the startup has no stored photo yet.
     * Later saves leave the existing one in place.
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'industry_sector' => ['required', 'string', 'max:100'],
            'business_description' => ['required', 'string'],
            'founder_name' => ['required', 'string', 'max:150'],
            'contact_phone' => ['required', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'startup_photo' => [
                Rule::requiredIf(fn () => blank($this->user()->startup?->startup_photo_path)),
                'image',
                'max:20480',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->boolean('startup_photo_upload_failed')) {
                $validator->errors()->add(
                    'startup_photo',
                    'That photo could not be uploaded. Please try again with an image under 20MB.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'contact_phone.required' => 'A phone number is required to complete your Startup Profile.',
            'location.required' => 'An address is required to complete your Startup Profile.',
            'startup_photo.required' => 'A startup photo is required to complete your Startup Profile.',
        ];
    }

    public function attributes(): array
    {
        return [
            'contact_phone' => 'phone number',
            'location' => 'address',
            'startup_photo' => 'startup photo',
            'founder_name' => 'founder name',
        ];
    }
}

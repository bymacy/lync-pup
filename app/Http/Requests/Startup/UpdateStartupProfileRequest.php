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
            // Business Description lives on the Startup itself (see
            // migration 000048), so it's a normal Profile field - no lock
            // to check here.
            'business_description' => ['required', 'string', 'min:50'],
            'founder_name' => ['required', 'string', 'max:150'],
            'contact_phone' => ['required', 'string', 'max:13', 'regex:/^(09\d{9}|\+639\d{9})$/'],
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

            // Core Team (the Profile's own StartupTeamMember roster - see
            // migration 000049) must have at least 3 members — tally what
            // survives this save: existing rows minus the ones marked for
            // deletion, plus any new, non-blank rows being added. Always
            // enforced: this roster is independent of the Information
            // Sheet's lock.
            $startup = $this->user()->startup;

            if ($startup) {
                $deletedIds = collect($this->input('deleted_team_members', []))
                    ->map(fn ($id) => (int) $id);

                $remainingExisting = $startup->startupTeamMembers()
                    ->whereNotIn('startup_team_member_id', $deletedIds)
                    ->count();

                $newCount = collect($this->input('new_team_members', []))
                    ->filter(fn ($name) => filled($name))
                    ->count();

                if (($remainingExisting + $newCount) < 3) {
                    $validator->errors()->add(
                        'team_members',
                        'Your Core Team must have at least 3 members.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'contact_phone.required' => 'A phone number is required to complete your Startup Profile.',
            'contact_phone.regex' => 'Enter a valid Philippine mobile number, for example 09171234567 or +639171234567.',
            'location.required' => 'An address is required to complete your Startup Profile.',
            'startup_photo.required' => 'A startup photo is required to complete your Startup Profile.',
            'business_description.min' => 'Your business description must be at least 50 characters.',
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

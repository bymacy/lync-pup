<?php

namespace App\Http\Requests\Startup;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStartupProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStartup();
    }

    protected function prepareForValidation(): void
    {
        if ($this->hasFile('startup_photo') && ! $this->file('startup_photo')->isValid()) {
            $this->files->remove('startup_photo');
        }
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'industry_sector' => ['required', 'string', 'max:100'],
            'business_description' => ['required', 'string'],
            'founder_name' => ['required', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'startup_photo' => ['nullable', 'image', 'max:20480'],
        ];
    }
}
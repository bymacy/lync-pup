<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreMentorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        if ($this->hasFile('mentor_photo') && ! $this->file('mentor_photo')->isValid()) {
            $this->files->remove('mentor_photo');
        }
    }

    public function rules(): array
    {
        return [
            'honorific' => ['required', 'string', 'in:Mr.,Ms.,Mrs.,Dr.,Prof.,Atty.,Engr.'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'specialization' => ['required', 'string', 'in:Engineering,Business,Marketing,Legal,Finance,Technology'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'mentor_photo' => ['nullable', 'image', 'max:20480'], // 20MB raw upload cap; gets compressed to ~2MB on save,
        ];
    }

    public function messages(): array
    {
        return [
            'honorific.required' => 'Please select an honorific.',
            'specialization.required' => 'Please select an expertise.',
        ];
    }
}
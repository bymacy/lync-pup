<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoordinatorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    protected function prepareForValidation(): void
    {
        if ($this->hasFile('coordinator_photo') && ! $this->file('coordinator_photo')->isValid()) {
            $this->files->remove('coordinator_photo');
        }
    }

    public function rules(): array
    {
        return [
            'honorific' => ['required', 'string', 'in:Sir,Ma\'am,Mr.,Ms.,Mrs.,Dr.,Prof.,Atty.,Engr.'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'coordinator_photo' => ['nullable', 'image', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'honorific.required' => 'Please select an honorific.',
        ];
    }
}
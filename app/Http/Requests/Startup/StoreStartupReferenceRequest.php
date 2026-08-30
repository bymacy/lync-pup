<?php

namespace App\Http\Requests\Startup;

use Illuminate\Foundation\Http\FormRequest;

class StoreStartupReferenceRequest extends FormRequest
{
    use SheetRowRules;

    public function authorize(): bool
    {
        return $this->user()->isStartup();
    }

    /**
     * Item 35 of the Information Sheet. Every column is required — see
     * SheetRowRules for why, and for the shared column shapes.
     */
    public function rules(): array
    {
        return [
            'name' => $this->rowName(150),
            'contact' => $this->rowPhone(),
            'email' => ['required', 'email', 'max:150'],
            'address' => $this->rowText(255),
        ];
    }

    public function messages(): array
    {
        return $this->rowMessages([
            'name.required' => 'Enter the reference\'s full name.',
            'contact.required' => 'Enter the reference\'s mobile number.',
            'contact.regex' => 'Enter a valid Philippine mobile number, for example 09171234567.',
            'email.required' => 'Enter the reference\'s email address.',
            'address.required' => 'Enter the reference\'s address.',
        ]);
    }
}

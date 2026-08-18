<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class RegisterFounderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            // Mirrors the 4-item checklist shown on the registration form
            // (8+ chars, upper+lowercase, a number, a special character) so
            // the client-side meter and server-side validation always agree.
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
            'company_name' => ['required', 'string', 'max:150'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Please enter your startup or venture name.',
            'terms.accepted' => 'You must agree to the Terms of Service and Privacy Policy to continue.',
        ];
    }
}

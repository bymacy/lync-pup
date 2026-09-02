<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules;

class RegisterFounderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The framework's default failed-validation redirect flashes old input
     * through the global exception Handler, whose $dontFlash list excludes
     * BOTH password fields on every form app-wide — there is no per-request
     * override for that. So this form builds its own redirect instead:
     * Password is always kept (a founder shouldn't have to retype a
     * perfectly fine password just because, say, the Terms of Service box
     * was left unchecked), while Confirm Password is only cleared when the
     * mismatch itself is what failed — any other error leaves both fields
     * exactly as typed.
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        $except = $errors->has('password_confirmation') ? ['password_confirmation'] : [];

        throw new HttpResponseException(
            redirect($this->getRedirectUrl())
                ->withInput($this->except($except))
                ->withErrors($errors, $this->errorBag)
        );
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            // Mirrors the 4-item checklist shown on the registration form
            // (8+ chars, upper+lowercase, a number, a special character) so
            // the client-side meter and server-side validation always agree.
            'password' => ['required', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
            // 'same' rather than putting 'confirmed' on the password field
            // itself — 'confirmed' attaches its mismatch error to
            // 'password', which showed the "does not match" message under
            // the wrong field (Password instead of Confirm Password).
            'password_confirmation' => ['bail', 'required', 'same:password'],
            'company_name' => ['required', 'string', 'max:150'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Please enter your startup or venture name.',
            'terms.accepted' => 'You must agree to the Terms of Service and Privacy Policy to continue.',
            'password_confirmation.same' => 'The password field confirmation does not match.',
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreMentorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'honorific' => ['required', 'string', 'in:Mr.,Ms.,Mrs.,Dr.,Prof.,Atty.,Engr.'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'specialization' => ['required', 'string', 'in:Engineering,Business,Marketing,Legal,Finance,Technology'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_number' => ['nullable', 'digits:11'],
            'mentor_photo' => [
                'nullable',
                'image',
                'max:20480', // 20MB raw upload cap; gets compressed to ~2MB on save
                function ($attribute, $value, $fail) {
                    // A file that's present but !isValid() usually means the
                    // web server itself rejected it (e.g. it exceeded
                    // upload_max_filesize/post_max_size) before Laravel's
                    // other rules even got to look at it. Surface that
                    // clearly instead of silently dropping the photo.
                    if ($value instanceof UploadedFile && ! $value->isValid()) {
                        $fail('That photo could not be uploaded — it may be too large for the server to accept. Please try a smaller file.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'honorific.required' => 'Please select an honorific.',
            'specialization.required' => 'Please select an expertise.',
            'contact_number.digits' => 'Phone number must be exactly 11 digits (numbers only).',
            'mentor_photo.image' => 'That file is not a supported image format (JPG, PNG, GIF, or WEBP).',
            'mentor_photo.max' => 'Photo must be 20MB or smaller.',
        ];
    }
}
<?php

namespace App\Http\Requests\Startup;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStartup();
    }

    public function rules(): array
    {
        return ['full_name' => ['required', 'string', 'max:150']];
    }
}
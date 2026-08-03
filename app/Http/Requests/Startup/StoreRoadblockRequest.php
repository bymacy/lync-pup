<?php

namespace App\Http\Requests\Startup;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoadblockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStartup() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->hasFile('supporting_files')) {
            $files = collect($this->file('supporting_files'))
                ->filter(fn ($file) => $file && $file->isValid())
                ->values()
                ->all();

            $this->files->set('supporting_files', $files);
        }
    }

    public function rules(): array
    {
        return [
            'problem_category' => ['required', 'in:Business Development,Technical Support,Market Research,Strategy Consultant,Others'],
            'problem_category_other' => ['nullable', 'required_if:problem_category,Others', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'supporting_files' => ['nullable', 'array'],
            'supporting_files.*' => [
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,mp4',
            ],
        ];
    }
}

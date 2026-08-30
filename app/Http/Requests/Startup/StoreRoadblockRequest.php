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
                function ($attribute, $value, $fail) {
                    // Laravel's built-in "mimes" rule rejects a file whose
                    // *content-sniffed* MIME doesn't map cleanly back to one
                    // of the listed extensions (via Symfony's guessExtension()).
                    // Some perfectly normal PNGs/JPEGs — depending on the phone,
                    // app, or export tool that produced them — get sniffed as
                    // an alternate/legacy MIME string (or fail to guess an
                    // extension at all), so "mimes" silently rejected them even
                    // though nothing was actually wrong with the file. Trusting
                    // the file's own extension instead avoids that false
                    // rejection; CompressesImages separately guards against a
                    // genuinely unreadable image at the processing step.
                    $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'mp4'];
                    $ext = strtolower($value->getClientOriginalExtension());

                    if (! in_array($ext, $allowed, true)) {
                        $fail('The '.$attribute.' must be a file of type: '.implode(', ', $allowed).'.');
                    }
                },
            ],
        ];
    }
}

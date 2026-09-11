<?php

namespace App\Http\Requests\Startup;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the Information Sheet's "Add Supporting Documents" upload —
 * same limits as Roadblock submission's own "Supporting Files" (see
 * StoreRoadblockRequest): up to 5 files per batch, 10MB each, the same
 * allowed types.
 */
class StoreInformationSheetFilesRequest extends FormRequest
{
    public const MAX_FILES = 5;

    public const MAX_KILOBYTES = 10240;

    public const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'mp4'];

    public function authorize(): bool
    {
        return $this->user()->isStartup();
    }

    public function rules(): array
    {
        return [
            'files' => ['nullable', 'array', 'max:'.self::MAX_FILES],
            'files.*' => [
                'file',
                'mimes:'.implode(',', self::ALLOWED_EXTENSIONS),
                'max:'.self::MAX_KILOBYTES,
            ],
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentDocument;
use App\Models\ReadinessLevelAssessment;
use App\Models\SavedReport;
use App\Models\Startup;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use ZipArchive;

class ExportController extends Controller
{
    /**
     * Every document the checklist always shows, regardless of whether the
     * startup actually has data for it yet (explicit product decision: list
     * all 13 always). Keyed by the real PUP-TBIDO form number.
     */
    private const DOCUMENTS = [
        1 => ['label' => 'Startup Information Sheet', 'form_no' => '001'],
        2 => ['label' => 'Pre-Assessment TRL', 'form_no' => '002'],
        3 => ['label' => 'Pre-Assessment MRL', 'form_no' => '003'],
        4 => ['label' => 'Pre-Assessment TMRL', 'form_no' => '004'],
        5 => ['label' => 'Pre-Assessment SRL', 'form_no' => '005'],
        6 => ['label' => 'Startup Growth Strategy', 'form_no' => '006'],
        7 => ['label' => 'Weekly Check-Ins', 'form_no' => '007'],
        8 => ['label' => 'Prototype Validation Form', 'form_no' => '008'],
        9 => ['label' => 'Post-Assessment TRL', 'form_no' => '009'],
        10 => ['label' => 'Post-Assessment MRL', 'form_no' => '010'],
        11 => ['label' => 'Post-Assessment TMRL', 'form_no' => '011'],
        12 => ['label' => 'Post-Assessment SRL', 'form_no' => '012'],
        13 => ['label' => 'Startup Exit Form', 'form_no' => '013'],
    ];

    /**
     * Returns the checklist (document numbers + labels) for the "Select
     * Documents" step — used by the Export Document modal.
     */
    public function documents(): JsonResponse
    {
        return response()->json([
            'documents' => collect(self::DOCUMENTS)->map(fn ($doc, $num) => [
                'number' => $num,
                'label' => $doc['label'],
                'form_no' => $doc['form_no'],
            ])->values(),
        ]);
    }

    /**
     * Generates the actual PDF/ZIP file(s) for the selected startup +
     * documents + format, and stores them to the public disk. Nothing is
     * written to `saved_reports` yet — that only happens if the admin
     * explicitly clicks "Save to Reports" (see save() below). Downloading
     * straight from the "Export Completed" screen works regardless, since
     * the file already physically exists on disk at this point.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'startup_id' => ['required', 'integer', 'exists:startups,startup_id'],
            'document_numbers' => ['required', 'array', 'min:1'],
            'document_numbers.*' => ['integer', Rule::in(array_keys(self::DOCUMENTS))],
            'format' => ['required', Rule::in(['PDF Bundle', 'ZIP Archive', 'Individual PDFs'])],
            'file_name' => ['required', 'string', 'max:150'],
        ]);

        $startup = Startup::findOrFail($validated['startup_id']);

        $documentNumbers = collect($validated['document_numbers'])
            ->map(fn ($n) => (int) $n)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $format = $validated['format'];
        $baseName = $this->sanitizeFileName($validated['file_name']);
        $batch = (string) Str::uuid();
        $dir = "exports/{$startup->startup_id}/{$batch}";

        $sections = [];
        foreach ($documentNumbers as $num) {
            $sections[$num] = $this->renderDocumentContent($num, $startup);
        }

        $files = match ($format) {
            'PDF Bundle' => [$this->makeBundleFile($dir, $baseName, $sections)],
            'Individual PDFs' => $this->makeIndividualFiles($dir, $baseName, $sections),
            'ZIP Archive' => [$this->makeZipFile($dir, $baseName, $sections)],
        };

        return response()->json([
            'export_batch' => $batch,
            'startup_id' => $startup->startup_id,
            'format' => $format,
            'documents_included' => count($documentNumbers),
            'documents_total' => count(self::DOCUMENTS),
            'generated_at' => now()->toIso8601String(),
            'files' => $files,
        ]);
    }

    /**
     * Catalogs already-generated file(s) into `saved_reports` so they show
     * up under the Reports tab. The client echoes back exactly the file
     * list it received from generate() — each entry is re-validated here
     * (path must sit inside this startup's own batch folder, and the file
     * must actually exist) before a row is written.
     */
    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'startup_id' => ['required', 'integer', 'exists:startups,startup_id'],
            'export_batch' => ['required', 'uuid'],
            'files' => ['required', 'array', 'min:1'],
            'files.*.file_name' => ['required', 'string'],
            'files.*.file_path' => ['required', 'string'],
            'files.*.format' => ['required', Rule::in(['PDF Bundle', 'ZIP Archive', 'Individual PDFs'])],
            'files.*.document_numbers' => ['required', 'array'],
            'files.*.document_numbers.*' => ['integer'],
            'files.*.page_count' => ['nullable', 'integer'],
            'files.*.file_size_bytes' => ['required', 'integer'],
        ]);

        $startup = Startup::findOrFail($validated['startup_id']);
        $expectedPrefix = "exports/{$startup->startup_id}/{$validated['export_batch']}/";

        $saved = [];
        foreach ($validated['files'] as $file) {
            if (! str_starts_with($file['file_path'], $expectedPrefix)
                || ! Storage::disk('public')->exists($file['file_path'])) {
                continue;
            }

            $saved[] = SavedReport::create([
                'startup_id' => $startup->startup_id,
                'file_name' => $file['file_name'],
                'file_path' => $file['file_path'],
                'export_batch' => $validated['export_batch'],
                'format' => $file['format'],
                'document_numbers' => $file['document_numbers'],
                'page_count' => $file['page_count'] ?? 0,
                'file_size_bytes' => $file['file_size_bytes'],
                'generated_by' => $request->user()?->name,
            ]);
        }

        return response()->json([
            'saved_reports' => collect($saved)->map(fn (SavedReport $r) => [
                'saved_report_id' => $r->saved_report_id,
                'file_name' => $r->file_name,
                'file_size_label' => $r->file_size_label,
                'download_url' => route('admin.exports.download', $r),
            ])->values(),
        ]);
    }

    public function download(SavedReport $savedReport)
    {
        abort_unless(Storage::disk('public')->exists($savedReport->file_path), 404);

        return Storage::disk('public')->download($savedReport->file_path, $savedReport->file_name);
    }

    public function destroy(SavedReport $savedReport)
    {
        Storage::disk('public')->delete($savedReport->file_path);
        $savedReport->delete();

        return back()->with('status', 'Report file deleted.');
    }

    // ============ document content resolution ============

    protected function renderDocumentContent(int $documentNumber, Startup $startup): string
    {
        if ($documentNumber === 1) {
            return view('admin.exports._content-1', ['startup' => $startup])->render();
        }

        if (in_array($documentNumber, [2, 3, 4, 5, 9, 10, 11, 12], true)) {
            return $this->renderRubricContent($documentNumber, $startup);
        }

        if (in_array($documentNumber, [6, 7, 8], true)) {
            $document = AssessmentDocument::where('startup_id', $startup->startup_id)
                ->where('stage', 'Active-Assessment')
                ->where('document_number', $documentNumber)
                ->first();

            return view("admin.exports._content-{$documentNumber}", compact('startup', 'document'))->render();
        }

        // Document 13: Startup Exit Form.
        $document = AssessmentDocument::where('startup_id', $startup->startup_id)
            ->where('stage', 'Venture Exit')
            ->where('document_number', 13)
            ->first();

        return view('admin.exports._content-13', compact('startup', 'document'))->render();
    }

    protected function renderRubricContent(int $documentNumber, Startup $startup): string
    {
        [$type, $stage] = match ($documentNumber) {
            2 => ['TRL', 'Pre-Assessment'],
            3 => ['MRL', 'Pre-Assessment'],
            4 => ['TMRL', 'Pre-Assessment'],
            5 => ['SRL', 'Pre-Assessment'],
            9 => ['TRL', 'Post-Assessment'],
            10 => ['MRL', 'Post-Assessment'],
            11 => ['TMRL', 'Post-Assessment'],
            12 => ['SRL', 'Post-Assessment'],
        };

        $assessment = ReadinessLevelAssessment::where('startup_id', $startup->startup_id)
            ->where('stage', $stage)
            ->first();

        return view('admin.exports._rubric-content', compact('startup', 'type', 'stage', 'assessment'))->render();
    }

    // ============ file builders ============

    /**
     * @param  array<int, string>  $sections  document number => rendered content-only HTML
     */
    protected function makeBundleFile(string $dir, string $baseName, array $sections): array
    {
        $pdf = Pdf::loadView('admin.exports.bundle', ['sections' => array_values($sections)])->setPaper('a4');
        $binary = $pdf->output();

        $fileName = "{$baseName}.pdf";
        $path = "{$dir}/{$fileName}";
        Storage::disk('public')->put($path, $binary);

        return [
            'file_name' => $fileName,
            'file_path' => $path,
            'format' => 'PDF Bundle',
            'document_numbers' => array_keys($sections),
            'page_count' => $this->pageCount($pdf),
            'file_size_bytes' => strlen($binary),
            'file_size_label' => $this->sizeLabel(strlen($binary)),
            'download_url' => Storage::disk('public')->url($path),
        ];
    }

    protected function makeIndividualFiles(string $dir, string $baseName, array $sections): array
    {
        $files = [];

        foreach ($sections as $num => $html) {
            $pdf = Pdf::loadView('admin.exports.bundle', ['sections' => [$html]])->setPaper('a4');
            $binary = $pdf->output();

            $label = self::DOCUMENTS[$num]['label'];
            $fileName = $this->sanitizeFileName("{$baseName} - {$label}").'.pdf';
            $path = "{$dir}/{$fileName}";
            Storage::disk('public')->put($path, $binary);

            $files[] = [
                'file_name' => $fileName,
                'file_path' => $path,
                'format' => 'Individual PDFs',
                'document_numbers' => [$num],
                'page_count' => $this->pageCount($pdf),
                'file_size_bytes' => strlen($binary),
                'file_size_label' => $this->sizeLabel(strlen($binary)),
                'download_url' => Storage::disk('public')->url($path),
            ];
        }

        return $files;
    }

    protected function makeZipFile(string $dir, string $baseName, array $sections): array
    {
        $fileName = "{$baseName}.zip";
        $path = "{$dir}/{$fileName}";

        // Ensure the directory exists on disk before ZipArchive opens a
        // real filesystem path there.
        Storage::disk('public')->put($path, '');
        $absolutePath = Storage::disk('public')->path($path);

        $zip = new ZipArchive();
        $zip->open($absolutePath, ZipArchive::OVERWRITE);

        $totalPages = 0;
        foreach ($sections as $num => $html) {
            $pdf = Pdf::loadView('admin.exports.bundle', ['sections' => [$html]])->setPaper('a4');
            $binary = $pdf->output();
            $totalPages += $this->pageCount($pdf) ?? 0;

            $label = self::DOCUMENTS[$num]['label'];
            $zip->addFromString($this->sanitizeFileName($label).'.pdf', $binary);
        }

        $zip->close();

        $size = Storage::disk('public')->size($path);

        return [
            'file_name' => $fileName,
            'file_path' => $path,
            'format' => 'ZIP Archive',
            'document_numbers' => array_keys($sections),
            'page_count' => $totalPages ?: null,
            'file_size_bytes' => $size,
            'file_size_label' => $this->sizeLabel($size),
            'download_url' => Storage::disk('public')->url($path),
        ];
    }

    // ============ helpers ============

    protected function pageCount($pdf): ?int
    {
        try {
            $domPdf = $pdf->getDomPDF();
            $canvas = method_exists($domPdf, 'getCanvas') ? $domPdf->getCanvas() : $domPdf->get_canvas();

            return $canvas->get_page_count();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function sanitizeFileName(string $name): string
    {
        $name = trim(preg_replace('/[\/\\\\:*?"<>|]+/', '-', $name));

        return $name !== '' ? $name : 'Export';
    }

    protected function sizeLabel(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1).' MB';
        }

        return round(max($bytes, 1) / 1024, 1).' KB';
    }
}

<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Startup\StoreRoadblockRequest;
use App\Models\AssessmentDocument;
use App\Models\Roadblock;
use App\Traits\CompressesImages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoadblockController extends Controller
{
    use CompressesImages;

    public function index()
    {
        Roadblock::promoteEndedMeetingsToPendingReview();

        $startup = Auth::user()->startup;

        $roadblocks = Roadblock::with('files')
            ->where('startup_id', $startup->startup_id)
            ->latest()
            ->get();

        // "Others" category suggestions — pulled from every startup's past submissions
        // (not just this one) so the free-text field can predict what other startups
        // have already typed for the same kind of roadblock.
        $otherCategorySuggestions = Roadblock::where('problem_category', 'Others')
            ->whereNotNull('problem_category_other')
            ->where('problem_category_other', '!=', '')
            ->distinct()
            ->orderBy('problem_category_other')
            ->pluck('problem_category_other')
            ->values();

        // Weekly Update tab — reads the admin's Document 7 ("Weekly Check-ins")
        // rows for this startup rather than any dedicated founder-facing model;
        // this is the same JSON the admin edits under Active-Assessment.
        $doc7 = AssessmentDocument::where('startup_id', $startup->startup_id)
            ->where('stage', 'Active-Assessment')
            ->where('document_number', 7)
            ->first();

        $weeklyUpdates = collect($doc7?->data['check_ins'] ?? [])
            // Document 7 always seeds 10 blank rows for the admin to fill in —
            // only rows the admin actually typed something into should ever
            // reach the founder.
            ->filter(fn ($row) => collect($row)->contains(fn ($value) => trim((string) $value) !== ''))
            ->sortByDesc(fn ($row) => $row['dates'] ?? '')
            ->values();

        return view('startup.roadblocks.index', [
            'roadblocks' => $roadblocks,
            'otherCategorySuggestions' => $otherCategorySuggestions,
            'weeklyUpdates' => $weeklyUpdates,
        ]);
    }

    public function store(StoreRoadblockRequest $request)
    {
        $startup = Auth::user()->startup;

        // Wrapped in a transaction so a file that fails to process (an
        // unreadable image, say) rolls back the Roadblock row along with
        // it, instead of leaving a half-submitted roadblock with some
        // files silently missing and no error ever surfaced to the founder.
        DB::transaction(function () use ($request, $startup) {
            $roadblock = Roadblock::create([
                'startup_id' => $startup->startup_id,
                'problem_category' => $request->validated('problem_category'),
                'problem_category_other' => $request->validated('problem_category_other'),
                'description' => $request->validated('description'),
                'status' => 'Pending',
            ]);

            foreach ($request->file('supporting_files', []) as $file) {
                $isImage = str_starts_with($file->getMimeType(), 'image/');

                try {
                    $path = $isImage
                        ? $this->compressAndStoreImage($file, "roadblocks/{$roadblock->roadblock_id}")
                        : $file->store("roadblocks/{$roadblock->roadblock_id}", 'public');
                } catch (\RuntimeException $e) {
                    throw ValidationException::withMessages([
                        'supporting_files' => "\"{$file->getClientOriginalName()}\" couldn't be processed ({$e->getMessage()}). Please try a different file.",
                    ]);
                }

                $roadblock->files()->create([
                    'file_path' => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'is_image' => $isImage,
                ]);
            }
        });

        return redirect()
            ->route('startup.submissions.index', ['tab' => 'roadblock'])
            ->with('roadblock_submitted', true);
    }
}

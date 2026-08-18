<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Startup\StoreRoadblockRequest;
use App\Models\Roadblock;
use App\Traits\CompressesImages;
use Illuminate\Support\Facades\Auth;

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

        return view('startup.roadblocks.index', [
            'roadblocks' => $roadblocks,
            'otherCategorySuggestions' => $otherCategorySuggestions,
        ]);
    }

    public function store(StoreRoadblockRequest $request)
    {
        $startup = Auth::user()->startup;

        $roadblock = Roadblock::create([
            'startup_id' => $startup->startup_id,
            'problem_category' => $request->validated('problem_category'),
            'problem_category_other' => $request->validated('problem_category_other'),
            'description' => $request->validated('description'),
            'status' => 'Pending',
        ]);

        foreach ($request->file('supporting_files', []) as $file) {
            $isImage = str_starts_with($file->getMimeType(), 'image/');

            $path = $isImage
                ? $this->compressAndStoreImage($file, "roadblocks/{$roadblock->roadblock_id}")
                : $file->store("roadblocks/{$roadblock->roadblock_id}", 'public');

            $roadblock->files()->create([
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'is_image' => $isImage,
            ]);
        }

        return redirect()
            ->route('startup.submissions.index', ['tab' => 'roadblock'])
            ->with('roadblock_submitted', true);
    }
}

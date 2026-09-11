<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VersionHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Manages Version History log entries themselves — renaming an entry's
 * display label, or deleting the entry outright. Neither action ever
 * touches the underlying record (InformationSheet, EvaluationSchedule,
 * ReadinessLevelAssessment, AssessmentDocument) the entry describes; this
 * is a read-only activity log, not a data-restoring versioning system.
 */
class VersionHistoryController extends Controller
{
    public function update(Request $request, VersionHistory $versionHistory): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:150'],
        ]);

        // Blank/whitespace-only input reverts to the auto-generated
        // timestamp label rather than saving an empty string.
        $versionHistory->update([
            'label' => trim((string) ($data['label'] ?? '')) ?: null,
        ]);

        return back()->with('status', 'Version renamed.');
    }

    public function destroy(VersionHistory $versionHistory): RedirectResponse
    {
        $versionHistory->delete();

        return back()->with('status', 'Version history entry deleted.');
    }
}

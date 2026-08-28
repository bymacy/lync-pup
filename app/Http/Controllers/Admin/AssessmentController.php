<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentDocument;
use App\Models\ReadinessLevelAssessment;
use App\Models\Startup;
use App\Support\ReadinessRubric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    /**
     * Saves one stage's worth of TRL/MRL/TMRL/SRL checklist progress for a
     * startup. The form posts each RL type's progress as a JSON string
     * (serialized client-side from Alpine state) rather than a nested
     * array, since the checklists are keyed by level number with a
     * variable-length boolean array per level — awkward to express as
     * conventional bracketed form field names, but trivial as JSON.
     */
    public function update(Request $request, Startup $startup): RedirectResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:'.implode(',', ReadinessRubric::STAGES)],
            'trl_progress' => ['nullable', 'json'],
            'mrl_progress' => ['nullable', 'json'],
            'tmrl_progress' => ['nullable', 'json'],
            'srl_progress' => ['nullable', 'json'],
            // Only ever submitted from the Pre-Assessment TRL tab (Section 1:
            // Startup & Technology Overview) — absent everywhere else.
            'trl_overview' => ['nullable', 'json'],
            // Editable Date of Assessment picker — also only present on that
            // same TRL Pre-Assessment tab; falls back to today when absent.
            'assessment_date' => ['nullable', 'date'],
            // Signatory block at the end of the form — shared across every
            // RL type/stage, submitted once per save regardless of which
            // tab is active.
            'evaluated_by' => ['nullable', 'string', 'max:150'],
            'reviewed_by' => ['nullable', 'string', 'max:150'],
            'noted_by' => ['nullable', 'string', 'max:150'],
            // TRL's own signatory block ("Prepared By" / "Noted By" /
            // "Approved by") — distinct from the MRL block's fields above.
            // "Approved by" is editable but arrives pre-filled with the
            // director's fixed signature, so it's stored like its siblings.
            'prepared_by' => ['nullable', 'string', 'max:150'],
            'prepared_by_position' => ['nullable', 'string', 'max:150'],
            'trl_noted_by' => ['nullable', 'string', 'max:150'],
            'trl_noted_by_position' => ['nullable', 'string', 'max:150'],
            'approved_by' => ['nullable', 'string', 'max:150'],
            'approved_by_position' => ['nullable', 'string', 'max:1000'],
            // Position/title lines under the MRL block's three names —
            // editable-but-prefilled the same way as approved_by_position.
            'evaluated_by_position' => ['nullable', 'string', 'max:1000'],
            'reviewed_by_position' => ['nullable', 'string', 'max:150'],
            'noted_by_position' => ['nullable', 'string', 'max:1000'],
            // SRL's own Evaluated/Reviewed/Noted by block — distinct
            // columns from the MRL/TMRL block above (different default
            // "Reviewed by" title, so it can't share the same fields).
            'srl_evaluated_by' => ['nullable', 'string', 'max:150'],
            'srl_evaluated_by_position' => ['nullable', 'string', 'max:1000'],
            'srl_reviewed_by' => ['nullable', 'string', 'max:150'],
            'srl_reviewed_by_position' => ['nullable', 'string', 'max:150'],
            'srl_noted_by' => ['nullable', 'string', 'max:150'],
            'srl_noted_by_position' => ['nullable', 'string', 'max:1000'],
        ]);

        $assessment = ReadinessLevelAssessment::firstOrNew([
            'startup_id' => $startup->startup_id,
            'stage' => $validated['stage'],
        ]);

        foreach (ReadinessRubric::TYPES as $type) {
            $key = strtolower($type).'_progress';
            $assessment->{$key} = isset($validated[$key]) ? json_decode($validated[$key], true) : [];
        }

        if (isset($validated['trl_overview'])) {
            $assessment->trl_overview = json_decode($validated['trl_overview'], true);
        }

        $assessment->evaluated_by = $validated['evaluated_by'] ?? ($request->user()->name ?? $request->user()->email);
        $assessment->reviewed_by = $validated['reviewed_by'] ?? null;
        $assessment->noted_by = $validated['noted_by'] ?? null;
        $assessment->prepared_by = $validated['prepared_by'] ?? null;
        $assessment->prepared_by_position = $validated['prepared_by_position'] ?? null;
        $assessment->trl_noted_by = $validated['trl_noted_by'] ?? null;
        $assessment->trl_noted_by_position = $validated['trl_noted_by_position'] ?? null;
        $assessment->approved_by = $validated['approved_by'] ?? null;
        $assessment->approved_by_position = $validated['approved_by_position'] ?? null;
        $assessment->evaluated_by_position = $validated['evaluated_by_position'] ?? null;
        $assessment->reviewed_by_position = $validated['reviewed_by_position'] ?? null;
        $assessment->noted_by_position = $validated['noted_by_position'] ?? null;
        $assessment->srl_evaluated_by = $validated['srl_evaluated_by'] ?? null;
        $assessment->srl_evaluated_by_position = $validated['srl_evaluated_by_position'] ?? null;
        $assessment->srl_reviewed_by = $validated['srl_reviewed_by'] ?? null;
        $assessment->srl_reviewed_by_position = $validated['srl_reviewed_by_position'] ?? null;
        $assessment->srl_noted_by = $validated['srl_noted_by'] ?? null;
        $assessment->srl_noted_by_position = $validated['srl_noted_by_position'] ?? null;
        $assessment->assessment_date = $validated['assessment_date'] ?? now();
        $assessment->recomputeScores();
        $assessment->save();

        return back()->with('status', 'assessment-saved')
            ->with('assessed_startup', $startup->startup_id)
            ->with('assessed_stage', $validated['stage']);
    }

    /**
     * Saves a stage's free-form "document" forms for a startup — Document
     * 6/7/8 under Active-Assessment, and the Startup Exit Form (document 13)
     * under Venture Exit. Each document's full set of field values is
     * posted as one JSON string (serialized client-side from Alpine state),
     * same approach as update() above — these documents mix free text,
     * checkbox grids, and variable-length repeating tables, which doesn't
     * map cleanly onto plain bracketed form field names.
     */
    public function updateDocuments(Request $request, Startup $startup): RedirectResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:'.implode(',', ReadinessRubric::STAGES)],
            'document_6' => ['nullable', 'json'],
            'document_7' => ['nullable', 'json'],
            'document_8' => ['nullable', 'json'],
            'document_13' => ['nullable', 'json'],
        ]);

        foreach ([6, 7, 8, 13] as $documentNumber) {
            $key = 'document_'.$documentNumber;

            if (! array_key_exists($key, $validated)) {
                continue;
            }

            AssessmentDocument::updateOrCreate(
                [
                    'startup_id' => $startup->startup_id,
                    'stage' => $validated['stage'],
                    'document_number' => $documentNumber,
                ],
                ['data' => json_decode($validated[$key], true)]
            );
        }

        return back()->with('status', 'assessment-saved')
            ->with('assessed_startup', $startup->startup_id)
            ->with('assessed_stage', $validated['stage']);
    }
}

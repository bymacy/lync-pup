<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A read-only activity-log entry — see the migration's docblock. Rename only
 * ever edits `label`; Delete only ever removes the row itself. Neither
 * touches the InformationSheet/EvaluationSchedule/ReadinessLevelAssessment/
 * AssessmentDocument record the entry describes.
 */
class VersionHistory extends Model
{
    protected $table = 'version_histories';

    protected $primaryKey = 'version_history_id';

    protected $fillable = [
        'startup_id',
        'context',
        'action',
        'label',
        'user_id',
    ];

    /**
     * Machine action key => human-readable label. New action keys (for the
     * other modules this feature is meant to reach later — Cohort, Startup,
     * Mentor, Coordinator, Roadblock) just get appended here; no schema
     * change needed.
     */
    public const ACTION_LABELS = [
        'set_evaluation' => 'Set Evaluation',
        'reschedule_evaluation' => 'Rescheduled Evaluation',
        'approve_information_sheet' => 'Approved Information Sheet',
        'reject_information_sheet' => 'Rejected Information Sheet',
        'update_information_sheet' => 'Edited Information Sheet',
        'update_readiness_assessment' => 'Updated Readiness Assessment',
        'update_assessment_document' => 'Updated Assessment Document',
    ];

    public function startup()
    {
        return $this->belongsTo(Startup::class, 'startup_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }

    /**
     * What the panel shows as the entry's primary heading — the custom
     * rename if one was set, otherwise the formatted save time (mirroring
     * the "September 10, 4:17 PM" mockup).
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: $this->created_at->format('F j, g:i A');
    }

    /**
     * Records one history entry. $actor defaults to the current admin so
     * call sites don't have to thread auth()->user() through everywhere.
     */
    public static function record(Startup $startup, string $context, string $action, ?User $actor = null): self
    {
        return self::create([
            'startup_id' => $startup->startup_id,
            'context' => $context,
            'action' => $action,
            'user_id' => ($actor ?? auth()->user())?->id,
        ]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backs the new "Version History" panel — a read-only activity log, not a
 * data-restoring versioning system. Each row is one tracked action (who did
 * what, when) against a startup's Assessment Hub records (Information Sheet
 * or one of the four assessment stages). Rename/Delete only ever touch this
 * log entry itself (the `label` override, or the row outright) — they never
 * write back to the underlying InformationSheet/EvaluationSchedule/
 * ReadinessLevelAssessment/AssessmentDocument records those actions describe.
 *
 * Piloted on the Assessment Hub only for now; the same table/pattern is
 * meant to extend to Cohort/Startup/Mentor/Coordinator/Roadblock management
 * later without a schema change (see VersionHistory::ACTION_LABELS).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('version_histories', function (Blueprint $table) {
            $table->id('version_history_id');
            $table->foreignId('startup_id')->constrained('startups', 'startup_id')->cascadeOnDelete();
            // Which panel this entry belongs to: 'Information Sheet' or one
            // of ReadinessRubric::STAGES ('Pre-Assessment', 'Active-Assessment',
            // 'Post-Assessment', 'Venture Exit'). Kept as a plain string
            // (not an enum) so future contexts — Cohort/Startup/Mentor/
            // Coordinator/Roadblock — can reuse this same table.
            $table->string('context');
            // Machine key describing what happened — see
            // VersionHistory::ACTION_LABELS for the human-readable label.
            $table->string('action');
            // Null until an admin renames this entry; falls back to the
            // formatted created_at timestamp when null (see
            // VersionHistory::getDisplayLabelAttribute()).
            $table->string('label')->nullable();
            // Nullable + set-null-on-delete: a deleted admin account
            // shouldn't take the history entry down with it — it just
            // stops being able to attribute an actor's name.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['startup_id', 'context']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('version_histories');
    }
};

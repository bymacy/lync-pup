<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backs the Assessment Hub's "Meetings" sub-nav (see the Assessment tab's
 * new "Meetings" stage pill, distinct from Overview/Pre-Assessment/Active-
 * Assessment/Post-Assessment/Venture Exit/Reports) — lets an admin schedule
 * a meeting with an already-approved startup ahead of actually filling in
 * that stage's RL/document forms. Deliberately its own table rather than
 * reusing evaluation_schedules: that one is tightly coupled to the
 * Information Sheet approval workflow (Scheduled/Cancelled status,
 * approvedOnEvaluationDay()/isMissed() outcome logic keyed off
 * informationSheet.approval_status) — none of which applies here. No
 * status column of its own either: Today/Upcoming/Archive (the sub-nav's
 * own filters) are purely derived from meeting_date vs today, same as
 * EvaluationSchedule::isToday()/isUpcoming() — see AssessmentMeeting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_meetings', function (Blueprint $table) {
            $table->id('assessment_meeting_id');
            $table->foreignId('startup_id')->constrained('startups', 'startup_id')->cascadeOnDelete();
            // Which of ReadinessRubric::STAGES this meeting is in service of
            // (Pre-Assessment / Active-Assessment / Post-Assessment /
            // Venture Exit) — the "Document (PRE, POST, Active, Exit)"
            // column in the Meetings table. Stage-level only, not tied to a
            // specific document number (Active-Assessment's Documents 6/7/8
            // share one meeting slot rather than needing three).
            $table->string('stage');
            $table->date('meeting_date');
            // Free-form start/end time (unlike evaluation_schedules' fixed
            // TIME_SLOTS) — these meetings vary in length and aren't tied to
            // a single formal "evaluation day".
            $table->time('start_time');
            $table->time('end_time');
            $table->string('modality');
            $table->string('link');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_meetings');
    }
};

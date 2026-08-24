<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TRL's "Section 1: Startup & Technology Overview" — a one-off intake
     * form (company/founder/tech-lead info, tech stack, team capacity,
     * etc.) that only appears above the TRL checklist on the Pre-Assessment
     * stage. Stored as JSON on the same row as that stage's TRL/MRL/TMRL/SRL
     * scores rather than a separate table, since it's saved together via
     * the same "Save Assessment" submit and only ever applies to one
     * (startup, stage) row at a time.
     */
    public function up(): void
    {
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->json('trl_overview')->nullable()->after('trl_progress');
        });
    }

    public function down(): void
    {
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->dropColumn('trl_overview');
        });
    }
};

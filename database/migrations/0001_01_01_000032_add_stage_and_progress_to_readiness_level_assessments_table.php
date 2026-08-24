<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Turns readiness_level_assessments from "one flat row per startup" into
     * "one row per startup PER STAGE" — Pre-Assessment, Active-Assessment,
     * Post-Assessment, and Venture Exit each keep their own independent
     * TRL/MRL/TMRL/SRL scores, rather than sharing a single live score.
     *
     * The *_progress JSON columns store which individual rubric criteria are
     * checked, per level, per RL type — e.g. trl_progress = {"1": [true,
     * true, true], "2": [false, false, true]}. The corresponding *_score
     * column is then derived (highest level whose criteria are ALL checked)
     * and saved alongside it, so existing read paths (latestReadinessAssessment,
     * the readiness-radar component, the various "RLS X.X" badges across the
     * app) keep working unchanged off the plain score columns.
     */
    public function up(): void
    {
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->string('stage', 30)->default('Pre-Assessment')->after('startup_id');
            $table->json('trl_progress')->nullable()->after('trl_score');
            $table->json('mrl_progress')->nullable()->after('mrl_score');
            $table->json('tmrl_progress')->nullable()->after('tmrl_score');
            $table->json('srl_progress')->nullable()->after('srl_score');
        });

        // One current row per startup per stage — saving re-uses the same
        // row (updateOrCreate) instead of piling up duplicate "Pre-Assessment"
        // rows for the same startup every time it's saved.
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->unique(['startup_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->dropUnique(['startup_id', 'stage']);
            $table->dropColumn(['stage', 'trl_progress', 'mrl_progress', 'tmrl_progress', 'srl_progress']);
        });
    }
};

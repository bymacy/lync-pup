<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The signatory block at the end of the Assessment form ("Evaluated
     * by" / "Reviewed by" / "Noted by") — `evaluated_by` already existed
     * (previously auto-filled from the logged-in admin and never shown in
     * the form), so this just adds its two counterparts and makes all
     * three editable from the form itself.
     */
    public function up(): void
    {
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->string('reviewed_by', 150)->nullable()->after('evaluated_by');
            $table->string('noted_by', 150)->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->dropColumn(['reviewed_by', 'noted_by']);
        });
    }
};

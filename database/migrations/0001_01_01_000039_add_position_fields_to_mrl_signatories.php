<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The MRL signatory block's "Evaluated by" / "Reviewed by" / "Noted by"
     * position/title lines were fixed text under each name field. They're
     * now editable-but-prefilled, matching the TRL block's "Approved by"
     * treatment, so they need storage. evaluated_by_position and
     * noted_by_position are text columns since each holds two lines.
     */
    public function up(): void
    {
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->text('evaluated_by_position')->nullable()->after('approved_by_position');
            $table->string('reviewed_by_position', 150)->nullable()->after('evaluated_by_position');
            $table->text('noted_by_position')->nullable()->after('reviewed_by_position');
        });
    }

    public function down(): void
    {
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->dropColumn(['evaluated_by_position', 'reviewed_by_position', 'noted_by_position']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SRL's own "Evaluated by" / "Reviewed by" / "Noted by" signatory
     * block. It looks like the shared MRL/TMRL block, but its "Reviewed
     * by" default title ("Incubation Management Chief, TBIDO") differs
     * from MRL/TMRL's ("Startup Development Chief, TBIDO") — since the
     * MRL/TMRL fields are one shared value across both tabs, SRL needs
     * its own distinct columns rather than a third default for the same
     * field.
     */
    public function up(): void
    {
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->string('srl_evaluated_by', 150)->nullable()->after('noted_by_position');
            $table->text('srl_evaluated_by_position')->nullable()->after('srl_evaluated_by');
            $table->string('srl_reviewed_by', 150)->nullable()->after('srl_evaluated_by_position');
            $table->string('srl_reviewed_by_position', 150)->nullable()->after('srl_reviewed_by');
            $table->string('srl_noted_by', 150)->nullable()->after('srl_reviewed_by_position');
            $table->text('srl_noted_by_position')->nullable()->after('srl_noted_by');
        });
    }

    public function down(): void
    {
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->dropColumn([
                'srl_evaluated_by', 'srl_evaluated_by_position',
                'srl_reviewed_by', 'srl_reviewed_by_position',
                'srl_noted_by', 'srl_noted_by_position',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TRL's own signatory block ("Prepared By" / "Noted By" — "Approved by"
     * is a fixed director signature and isn't stored). Named distinctly
     * from the existing evaluated_by/reviewed_by/noted_by columns, which
     * belong to the separate MRL signatory block on this same row.
     */
    public function up(): void
    {
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->string('prepared_by', 150)->nullable()->after('noted_by');
            $table->string('prepared_by_position', 150)->nullable()->after('prepared_by');
            $table->string('trl_noted_by', 150)->nullable()->after('prepared_by_position');
            $table->string('trl_noted_by_position', 150)->nullable()->after('trl_noted_by');
        });
    }

    public function down(): void
    {
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->dropColumn(['prepared_by', 'prepared_by_position', 'trl_noted_by', 'trl_noted_by_position']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Approved by" in TRL's signatory block — originally fixed text (the
     * director's own signature block), now editable-but-prefilled instead,
     * so it needs storage like its "Prepared By" / "Noted By" siblings.
     * approved_by_position is a text column (not string) since it holds
     * two lines (title + project role) rather than a single line.
     */
    public function up(): void
    {
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->string('approved_by', 150)->nullable()->after('trl_noted_by_position');
            $table->text('approved_by_position')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('readiness_level_assessments', function (Blueprint $table) {
            $table->dropColumn(['approved_by', 'approved_by_position']);
        });
    }
};

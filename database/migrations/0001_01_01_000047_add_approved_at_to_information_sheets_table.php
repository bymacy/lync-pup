<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the sheet was approved, not just that it was. The Assessment Hub's
     * evaluation roster marks a slot DONE only if approval landed on the
     * evaluation day itself (see EvaluationSchedule::approvedOnEvaluationDay()),
     * and approval_status alone cannot answer that.
     *
     * Nullable, and rows approved before this column existed stay null: those
     * are treated as approved-in-time rather than retroactively marked missed.
     */
    public function up(): void
    {
        Schema::table('information_sheets', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('information_sheets', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};

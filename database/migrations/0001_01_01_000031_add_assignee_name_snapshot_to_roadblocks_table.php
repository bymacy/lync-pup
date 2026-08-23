<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * mentor_id/coordinator_id are ON DELETE SET NULL, so deleting a
     * Mentor or Coordinator silently erases their name from any already
     * Resolved/Failed roadblock they used to be attached to (active
     * Scheduled/Pending Review ones get reset back to Pending instead —
     * see Roadblock::pendingResetAttributes()). For closed-out roadblocks,
     * losing the name entirely isn't acceptable: the Archive should still
     * say who handled it, just marked as no longer around. This column
     * captures that name at the moment of deletion so it survives it.
     */
    public function up(): void
    {
        Schema::table('roadblocks', function (Blueprint $table) {
            $table->string('assignee_name_snapshot')->nullable()->after('coordinator_id');
        });
    }

    public function down(): void
    {
        Schema::table('roadblocks', function (Blueprint $table) {
            $table->dropColumn('assignee_name_snapshot');
        });
    }
};

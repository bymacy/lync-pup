<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a roadblock be handled by a Coordinator instead of a Mentor —
     * sometimes the coordinator already knows the specific problem and
     * mentors the startup through it directly, so the "Assign & Schedule"
     * dropdown needs to be able to point at either kind of person.
     *
     * Exactly one of mentor_id / coordinator_id is expected to be set at a
     * time (both null means unassigned); enforced at the application layer
     * in AssignRoadblockRequest, not with a DB constraint.
     */
    public function up(): void
    {
        Schema::table('roadblocks', function (Blueprint $table) {
            $table->foreignId('coordinator_id')->nullable()->after('mentor_id')
                ->constrained('coordinators', 'coordinator_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('roadblocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coordinator_id');
        });
    }
};

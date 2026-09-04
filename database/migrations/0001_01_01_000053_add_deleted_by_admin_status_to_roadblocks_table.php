<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a "Deleted by Admin" status. Previously, an admin "deleting" a
     * roadblock (from the Failed archive, or via "Delete Assignment" on a
     * Scheduled one) hard-deleted the row outright — which also erased it
     * from the founder's own Archive with no trace of what happened to it.
     * Now that action instead moves the roadblock to this terminal status,
     * so the founder's Archive can still show it (and filter by it)
     * instead of the submission just silently vanishing.
     *
     * MySQL: widen the enum in place with a raw MODIFY so existing status
     * values are preserved. SQLite (only ever used for the automated test
     * suite here, always a fresh in-memory DB with no data to lose) doesn't
     * support MODIFY COLUMN / ENUM syntax at all, so it gets the drop+recreate
     * approach already established by the 000023/000025 migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE roadblocks MODIFY COLUMN status ENUM('Pending', 'Scheduled', 'Pending Review', 'Resolved', 'Failed', 'Deleted by Admin') NOT NULL DEFAULT 'Pending'");

            return;
        }

        Schema::table('roadblocks', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('roadblocks', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Scheduled', 'Pending Review', 'Resolved', 'Failed', 'Deleted by Admin'])
                ->default('Pending')
                ->after('problem_category_other');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Fold any "Deleted by Admin" rows back into "Failed" before
            // shrinking the enum, since the old definition doesn't accept it.
            DB::table('roadblocks')->where('status', 'Deleted by Admin')->update(['status' => 'Failed']);

            DB::statement("ALTER TABLE roadblocks MODIFY COLUMN status ENUM('Pending', 'Scheduled', 'Pending Review', 'Resolved', 'Failed') NOT NULL DEFAULT 'Pending'");

            return;
        }

        Schema::table('roadblocks', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('roadblocks', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Scheduled', 'Pending Review', 'Resolved', 'Failed'])
                ->default('Pending')
                ->after('problem_category_other');
        });
    }
};

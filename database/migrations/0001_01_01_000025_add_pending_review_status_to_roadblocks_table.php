<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a "Pending Review" status between Scheduled and Resolved/Failed:
     * once a roadblock's meeting has taken place (or it's been Recovered from
     * Resolved), it moves to Pending Review to await the admin's final call.
     *
     * MySQL: widen the enum in place with a raw MODIFY so existing status
     * values are preserved. SQLite (only ever used for the automated test
     * suite here, always a fresh in-memory DB with no data to lose) doesn't
     * support MODIFY COLUMN / ENUM syntax at all, so it gets the drop+recreate
     * approach already established by the 000023 migration.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
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

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Fold any "Pending Review" rows back into "Scheduled" before shrinking
            // the enum, since the old definition doesn't accept "Pending Review".
            DB::table('roadblocks')->where('status', 'Pending Review')->update(['status' => 'Scheduled']);

            DB::statement("ALTER TABLE roadblocks MODIFY COLUMN status ENUM('Pending', 'Scheduled', 'Resolved', 'Failed') NOT NULL DEFAULT 'Pending'");

            return;
        }

        Schema::table('roadblocks', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('roadblocks', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Scheduled', 'Resolved', 'Failed'])
                ->default('Pending')
                ->after('problem_category_other');
        });
    }
};

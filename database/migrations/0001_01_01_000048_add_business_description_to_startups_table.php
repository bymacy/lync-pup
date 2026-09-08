<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Startup Profile gets its own business_description column instead
     * of borrowing information_sheets.business_description.
     *
     * They used to be the same column: every Startup Profile save rewrote
     * informationSheet.business_description directly, which meant editing
     * the Profile could silently rewrite an already-approved (locked)
     * Information Sheet. That mirrors exactly why startup_overview was split
     * off the same column back in migration 000043 - same fix, other side.
     *
     * The sheet's own copy (still the same information_sheets.business_
     * description column) is now only ever pre-filled from this one while
     * it's still blank (see StartupProfileController::update()); after that
     * the two are fully independent.
     */
    public function up(): void
    {
        Schema::table('startups', function (Blueprint $table) {
            if (! Schema::hasColumn('startups', 'business_description')) {
                $table->text('business_description')->nullable()->after('industry_sector');
            }
        });

        // Seed existing startups from whatever their Information Sheet
        // already has, so nothing looks blank after the split. Written as a
        // correlated subquery (rather than Laravel's join()->update(), which
        // Laravel can only translate into a real SQL UPDATE...JOIN on
        // MySQL/Postgres) because SQLite has no UPDATE...JOIN at all - on
        // SQLite, join()->update() silently drops the join from the actual
        // UPDATE statement and leaves the raw "information_sheets.business_
        // description" reference in the SET clause dangling, which fails
        // with "no such column" the moment this migration runs against
        // SQLite (as it does for every test, since phpunit.xml points the
        // test database at :memory: SQLite). A correlated subquery is plain
        // ANSI SQL, so it runs the same way on both.
        if (Schema::hasColumn('startups', 'business_description')) {
            DB::statement('
                UPDATE startups
                SET business_description = (
                    SELECT information_sheets.business_description
                    FROM information_sheets
                    WHERE information_sheets.startup_id = startups.startup_id
                )
                WHERE business_description IS NULL
                AND EXISTS (
                    SELECT 1 FROM information_sheets
                    WHERE information_sheets.startup_id = startups.startup_id
                    AND information_sheets.business_description IS NOT NULL
                )
            ');
        }
    }

    public function down(): void
    {
        Schema::table('startups', function (Blueprint $table) {
            if (Schema::hasColumn('startups', 'business_description')) {
                $table->dropColumn('business_description');
            }
        });
    }
};

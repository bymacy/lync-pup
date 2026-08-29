<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Information Sheet gets its own overview text, separate from the
     * Startup Profile's business_description.
     *
     * They used to be the same column, so editing field 33 on the sheet also
     * rewrote the Profile (and vice versa). The sheet is a signed-off form —
     * its wording should be able to differ from the marketing blurb on the
     * Profile — so it now keeps its own copy, pre-filled from
     * business_description the first time and independent after that.
     */
    public function up(): void
    {
        Schema::table('information_sheets', function (Blueprint $table) {
            if (! Schema::hasColumn('information_sheets', 'startup_overview')) {
                $table->text('startup_overview')->nullable()->after('business_description');
            }
        });

        // Seed existing sheets so nothing looks blank after the split.
        if (Schema::hasColumn('information_sheets', 'startup_overview')) {
            \Illuminate\Support\Facades\DB::table('information_sheets')
                ->whereNull('startup_overview')
                ->update(['startup_overview' => \Illuminate\Support\Facades\DB::raw('business_description')]);
        }
    }

    public function down(): void
    {
        Schema::table('information_sheets', function (Blueprint $table) {
            if (Schema::hasColumn('information_sheets', 'startup_overview')) {
                $table->dropColumn('startup_overview');
            }
        });
    }
};

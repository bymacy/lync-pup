<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Self-service Founder registration only collects a Startup/Venture
     * Name up front — industry_sector (and everything else about the
     * startup) gets filled in later via the founder's own profile/
     * information sheet. Was NOT NULL, which would have blocked
     * registration from creating the Startup row at all.
     */
    public function up(): void
    {
        Schema::table('startups', function (Blueprint $table) {
            $table->string('industry_sector', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Not reversible without knowing what to backfill null rows with —
        // intentionally left as a no-op rather than risk a failed migration
        // on down() if any rows were created with a null industry_sector.
    }
};

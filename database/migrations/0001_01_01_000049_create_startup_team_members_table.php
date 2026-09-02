<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Startup Profile gets its own Core Team roster, separate from the
     * Information Sheet's team_members table (Section II).
     *
     * They used to be the exact same rows: the Profile page's "Team
     * Members" section read and wrote team_members.full_name directly,
     * so editing the roster there also edited Section II of an
     * already-approved (locked) Information Sheet - the same bug
     * business_description had (see migration 000048), just for a list
     * instead of a single column.
     *
     * The Information Sheet's own roster (still the same team_members
     * table, with all its biographical columns) is now only ever seeded
     * from this one, once, while it's still empty for that startup (see
     * StartupProfileController::update()); after that the two rosters are
     * fully independent - adding, renaming or removing someone on either
     * page never touches the other.
     */
    public function up(): void
    {
        Schema::create('startup_team_members', function (Blueprint $table) {
            $table->id('startup_team_member_id');
            $table->foreignId('startup_id')->constrained('startups', 'startup_id')->cascadeOnDelete();
            $table->string('full_name', 150);
            $table->timestamps();
        });

        // Seed every startup's Profile roster from whatever Core Team rows
        // they already have, so nothing looks empty after the split.
        $now = now();

        DB::table('team_members')
            ->orderBy('member_id')
            ->select('member_id', 'startup_id', 'full_name')
            ->chunkById(200, function ($rows) use ($now) {
                DB::table('startup_team_members')->insert(
                    $rows->map(fn ($row) => [
                        'startup_id' => $row->startup_id,
                        'full_name' => $row->full_name,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            }, 'member_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_team_members');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modality/link for the evaluation meeting itself — same idea as
     * Roadblock's meeting_platform/meeting_link (see
     * 0001_01_01_000023_add_mentor_assignment_to_roadblocks_table.php), added
     * here because an evaluation can now happen online just as easily as
     * face-to-face, and the admin needs to say which plus where/how to join.
     * Nullable so existing scheduled rows created before this migration
     * aren't broken by a NOT NULL default.
     */
    public function up(): void
    {
        Schema::table('evaluation_schedules', function (Blueprint $table) {
            $table->string('modality')->nullable()->after('end_time');
            $table->string('link')->nullable()->after('modality');
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_schedules', function (Blueprint $table) {
            $table->dropColumn(['modality', 'link']);
        });
    }
};

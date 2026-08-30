<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backs the founder dashboard's "what's new" cards. Nothing in the app
     * tracked whether a founder had actually looked at an admin-side change
     * (a scheduled mentorship, a released readiness score, a weekly check-in
     * typed into Document 7), so those updates landed silently and the
     * dashboard had no way to tell "new" from "already read".
     *
     * Each column is stamped when the founder opens the matching page, and
     * the dashboard compares it against the source row's updated_at. NULL
     * means "never opened", so everything reads as new — which is the right
     * default for existing founders on the first load after this ships.
     */
    public function up(): void
    {
        Schema::table('startups', function (Blueprint $table) {
            $table->timestamp('meetings_seen_at')->nullable();
            $table->timestamp('submissions_seen_at')->nullable();
            $table->timestamp('readiness_seen_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('startups', function (Blueprint $table) {
            $table->dropColumn(['meetings_seen_at', 'submissions_seen_at', 'readiness_seen_at']);
        });
    }
};

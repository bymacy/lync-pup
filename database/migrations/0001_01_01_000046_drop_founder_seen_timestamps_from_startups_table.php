<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Superseded by the notifications table. The *_seen_at columns tracked
     * "has this founder opened that tab yet", which was only ever a stand-in
     * for real per-item read state — notifications.read_at does the job
     * properly, so these would otherwise sit unread and unwritten forever.
     *
     * Written as a drop rather than deleting the migration that added them,
     * so databases that already ran 000044 converge with ones that never did.
     */
    public function up(): void
    {
        Schema::table('startups', function (Blueprint $table) {
            $table->dropColumn(['meetings_seen_at', 'submissions_seen_at', 'readiness_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::table('startups', function (Blueprint $table) {
            $table->timestamp('meetings_seen_at')->nullable();
            $table->timestamp('submissions_seen_at')->nullable();
            $table->timestamp('readiness_seen_at')->nullable();
        });
    }
};

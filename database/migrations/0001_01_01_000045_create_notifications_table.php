<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel's standard database-notifications table, backing the founder
     * header bell. Rows are written at the moment an admin actually changes
     * something (see App\Notifications\*), so each one carries its own
     * created_at and read_at — which is what the derived "*_seen_at" approach
     * could never give: history, per-item read state, and "2 hours ago".
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

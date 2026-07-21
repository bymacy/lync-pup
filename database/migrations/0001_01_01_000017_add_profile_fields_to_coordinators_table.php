<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coordinators', function (Blueprint $table) {
            $table->string('honorific', 20)->nullable()->after('coordinator_id');
            $table->string('first_name', 100)->nullable()->after('honorific');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('coordinator_photo_path')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('coordinators', function (Blueprint $table) {
            $table->dropColumn(['honorific', 'first_name', 'last_name', 'coordinator_photo_path']);
        });
    }
};
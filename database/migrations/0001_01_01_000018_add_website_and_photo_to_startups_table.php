<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('startups', function (Blueprint $table) {
            $table->string('website')->nullable()->after('location');
            $table->string('startup_photo_path')->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('startups', function (Blueprint $table) {
            $table->dropColumn(['website', 'startup_photo_path']);
        });
    }
};
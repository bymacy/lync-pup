<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentors', function (Blueprint $table) {
            $table->string('honorific', 20)->nullable()->after('mentor_id');
            $table->string('first_name', 100)->nullable()->after('honorific');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('mentor_photo_path')->nullable()->after('organization');
        });
    }

    public function down(): void
    {
        Schema::table('mentors', function (Blueprint $table) {
            $table->dropColumn(['honorific', 'first_name', 'last_name', 'mentor_photo_path']);
        });
    }
};

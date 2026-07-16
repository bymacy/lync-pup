<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->string('designation', 100)->nullable()->after('full_name');
            $table->string('phone', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('citizenship', 100)->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('civil_status', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn(['designation', 'phone', 'address', 'date_of_birth', 'citizenship', 'sex', 'civil_status']);
        });
    }
};
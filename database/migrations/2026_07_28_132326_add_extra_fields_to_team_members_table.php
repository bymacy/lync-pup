<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->string('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('citizenship')->nullable();
            $table->string('sex')->nullable();
            $table->string('civil_status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn(['address', 'date_of_birth', 'citizenship', 'sex', 'civil_status']);
        });
    }
};

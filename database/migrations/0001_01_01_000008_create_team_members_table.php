<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id('member_id');
            $table->foreignId('startup_id')->constrained('startups', 'startup_id')->cascadeOnDelete();
            $table->string('full_name', 150);
            $table->string('role', 100)->nullable();
            $table->string('email', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
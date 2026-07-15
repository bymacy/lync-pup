<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startups', function (Blueprint $table) {
            $table->id('startup_id');
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->string('company_name', 150);
            $table->string('industry_sector', 100);
            $table->unsignedInteger('cohort_number')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('location', 255)->nullable();
            $table->timestamps();

            $table->index('industry_sector');
            $table->index('cohort_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startups');
    }
};
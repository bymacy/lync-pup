<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coordinator_assignments', function (Blueprint $table) {
            $table->id('assignment_id');
            $table->foreignId('startup_id')->constrained('startups', 'startup_id')->cascadeOnDelete();
            $table->foreignId('coordinator_id')->constrained('coordinators', 'coordinator_id')->cascadeOnDelete();
            $table->date('assigned_date');
            $table->enum('assignment_status', ['Active', 'Inactive', 'Completed'])->default('Active');
            $table->timestamps();

            $table->index(['startup_id', 'assignment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coordinator_assignments');
    }
};
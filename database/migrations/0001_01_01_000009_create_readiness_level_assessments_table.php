<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readiness_level_assessments', function (Blueprint $table) {
            $table->id('assessment_id');
            $table->foreignId('startup_id')->constrained('startups', 'startup_id')->cascadeOnDelete();
            $table->string('evaluated_by', 150)->nullable();
            $table->unsignedTinyInteger('trl_score')->nullable();
            $table->unsignedTinyInteger('mrl_score')->nullable();
            $table->unsignedTinyInteger('tmrl_score')->nullable();
            $table->unsignedTinyInteger('srl_score')->nullable();
            $table->float('overall_score')->nullable();
            $table->text('remarks')->nullable();
            $table->date('assessment_date');
            $table->timestamps();

            $table->index(['startup_id', 'assessment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readiness_level_assessments');
    }
};
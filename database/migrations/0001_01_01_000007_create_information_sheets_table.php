<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('information_sheets', function (Blueprint $table) {
            $table->id('info_sheet_id');
            $table->foreignId('startup_id')->constrained('startups', 'startup_id')->cascadeOnDelete();
            $table->text('business_description');
            $table->text('target_market')->nullable();
            $table->text('problem_statement')->nullable();
            $table->text('solution_offered')->nullable();
            $table->date('submission_date')->nullable();
            $table->enum('approval_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->text('evaluator_remarks')->nullable();
            $table->timestamps();

            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('information_sheets');
    }
};
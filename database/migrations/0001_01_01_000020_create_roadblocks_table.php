<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roadblocks', function (Blueprint $table) {
            $table->id('roadblock_id');
            $table->foreignId('startup_id')->constrained('startups', 'startup_id')->cascadeOnDelete();
            $table->enum('problem_category', [
                'Business Development',
                'Technical Support',
                'Market Research',
                'Strategy Consultant',
                'Others',
            ]);
            $table->text('description');
            $table->enum('status', ['Pending', 'Resolved'])->default('Pending');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roadblocks');
    }
};
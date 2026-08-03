<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roadblock_files', function (Blueprint $table) {
            $table->id('roadblock_file_id');
            $table->foreignId('roadblock_id')->constrained('roadblocks', 'roadblock_id')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_filename');
            $table->boolean('is_image')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roadblock_files');
    }
};
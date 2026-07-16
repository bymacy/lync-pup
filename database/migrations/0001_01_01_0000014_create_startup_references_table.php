<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('startup_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('info_sheet_id')->constrained('information_sheets', 'info_sheet_id')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('contact', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('startup_references');
    }
};
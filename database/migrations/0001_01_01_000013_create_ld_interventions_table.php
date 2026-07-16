<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ld_interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('info_sheet_id')->constrained('information_sheets', 'info_sheet_id')->cascadeOnDelete();
            $table->string('title', 255);
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('number_of_hours', 20)->nullable();
            $table->string('conducted_sponsored_by', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ld_interventions');
    }
};
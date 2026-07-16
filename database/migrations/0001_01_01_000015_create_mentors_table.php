<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentors', function (Blueprint $table) {
            $table->id('mentor_id');
            $table->string('full_name', 150);
            $table->string('specialization', 150)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('organization', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentors');
    }
};

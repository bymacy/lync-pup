<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors problem_category/problem_category_other on roadblocks: when
     * "Others" is picked for a mentor's Expertise, the actual typed value
     * rides on this separate column instead of overloading `specialization`
     * itself (which stays the literal "Others" so existing code that reads
     * `specialization` directly can still tell the two apart).
     */
    public function up(): void
    {
        Schema::table('mentors', function (Blueprint $table) {
            $table->string('specialization_other')->nullable()->after('specialization');
        });
    }

    public function down(): void
    {
        Schema::table('mentors', function (Blueprint $table) {
            $table->dropColumn('specialization_other');
        });
    }
};

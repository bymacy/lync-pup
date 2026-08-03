<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roadblocks', function (Blueprint $table) {
            $table->string('problem_category_other')->nullable()->after('problem_category');
        });
    }

    public function down(): void
    {
        Schema::table('roadblocks', function (Blueprint $table) {
            $table->dropColumn('problem_category_other');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['Admin', 'Startup'])
                  ->default('Startup')
                  ->after('email');

            $table->enum('account_status', ['Active', 'Inactive', 'Suspended'])
                  ->default('Active')
                  ->after('role');

            $table->boolean('is_first_login')
                  ->default(true)
                  ->after('account_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'account_status', 'is_first_login']);
        });
    }
};
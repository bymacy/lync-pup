<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Self-registered Founder accounts now need an account-level approval
     * gate (separate from the existing Information Sheet content-approval
     * flow): they land as "Pending" right after email verification, and an
     * admin must approve ("Active") or reject ("Rejected") before they can
     * actually sign in. Existing accounts (seeded/admin-created) keep
     * defaulting to "Active" — nothing changes for them.
     *
     * Same driver-aware pattern as the roadblocks status migration: MySQL
     * gets a raw MODIFY, SQLite (test suite) gets a drop+recreate since it
     * has no data to lose and doesn't support MODIFY COLUMN/ENUM syntax.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN account_status ENUM('Active', 'Inactive', 'Suspended', 'Pending', 'Rejected') NOT NULL DEFAULT 'Active'");

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_status', ['Active', 'Inactive', 'Suspended', 'Pending', 'Rejected'])
                ->default('Active')
                ->after('role');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('users')->whereIn('account_status', ['Pending', 'Rejected'])->update(['account_status' => 'Inactive']);
            DB::statement("ALTER TABLE users MODIFY COLUMN account_status ENUM('Active', 'Inactive', 'Suspended') NOT NULL DEFAULT 'Active'");

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('account_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('account_status', ['Active', 'Inactive', 'Suspended'])
                ->default('Active')
                ->after('role');
        });
    }
};

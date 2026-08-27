<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the fields the admin Dashboard's Cohort modals need (Start
     * Date, End Date, Description). The existing "Inactive" status value
     * is intentionally left as-is in the database rather than renamed to
     * "Archived" — the original column is a real `enum('Active',
     * 'Inactive')` CHECK constraint, and altering an enum's allowed
     * values isn't a safe, portable operation across drivers (SQLite in
     * particular can't ALTER a CHECK constraint in place without a full
     * table rebuild). "Archived" is purely a display label mapped from
     * the stored 'Inactive' value — see Cohort::getStatusLabelAttribute().
     */
    public function up(): void
    {
        Schema::table('cohorts', function ($table) {
            $table->date('start_date')->nullable()->after('label');
            $table->date('end_date')->nullable()->after('start_date');
            $table->text('description')->nullable()->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('cohorts', function ($table) {
            $table->dropColumn(['start_date', 'end_date', 'description']);
        });
    }
};

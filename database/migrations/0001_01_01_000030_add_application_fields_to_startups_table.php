<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fields needed by the admin "Founder Application" review screen:
     * which cohort the founder was approved into, the admin's remarks,
     * the reason given for a rejection, and when the decision was made
     * (used to render the "Status History" timeline).
     */
    public function up(): void
    {
        Schema::table('startups', function (Blueprint $table) {
            $table->foreignId('cohort_id')->nullable()->after('cohort_number')
                ->constrained('cohorts', 'cohort_id')->nullOnDelete();
            $table->text('admin_remarks')->nullable()->after('cohort_id');
            $table->string('rejection_reason', 255)->nullable()->after('admin_remarks');
            $table->timestamp('application_decided_at')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('startups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cohort_id');
            $table->dropColumn(['admin_remarks', 'rejection_reason', 'application_decided_at']);
        });
    }
};

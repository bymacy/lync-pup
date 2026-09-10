<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stamped every time an Information Sheet is rejected (see
     * Admin\InformationSheetController::reject()) and cleared the moment it
     * is approved (see ::approve()). Drives two things: the Rejected tab's
     * 10-day auto-delete countdown (see the founders:purge-expired-rejections
     * command), and the "Re-Evaluation" status tag shown once a rejected
     * startup resubmits — resubmission flips approval_status back to
     * 'Pending' (see Startup\InformationSheetController::update()) but
     * deliberately leaves this column alone, so its mere presence on an
     * otherwise-Pending sheet is what marks it as a resubmission rather than
     * a first-time submission.
     */
    public function up(): void
    {
        Schema::table('information_sheets', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('information_sheets', function (Blueprint $table) {
            $table->dropColumn('rejected_at');
        });
    }
};

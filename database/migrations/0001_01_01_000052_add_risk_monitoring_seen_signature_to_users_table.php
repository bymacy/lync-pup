<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backs the Risk Monitoring sidebar red-dot badge (see
     * App\Providers\AppServiceProvider::boot()'s admin sidebar view
     * composer). Risk indicators are computed live, not stored as discrete
     * timestamped events, so "new risk since I last looked" is tracked by
     * comparing a hash of the CURRENT set of triggered
     * startup/indicator pairs (cached under 'risk_monitoring_signature',
     * refreshed by Admin\RiskMonitoringController::index()) against the
     * hash this admin last saw. Null means "never visited yet" — always
     * shows the dot the first time, same as any other unseen state.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('risk_monitoring_seen_signature')->nullable()->after('email_verification_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('risk_monitoring_seen_signature');
        });
    }
};

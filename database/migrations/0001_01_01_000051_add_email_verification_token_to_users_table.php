<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel's stock email-verification signed URL is stateless — nothing
     * in the framework invalidates a previously-issued verification link
     * once a newer one is sent. This column backs that invalidation: a
     * fresh random value is generated and saved every time a verification
     * email goes out (see User::sendEmailVerificationNotification()), and
     * is embedded in the signed URL as an extra "token" query param.
     * VerifyEmailController rejects any link whose token doesn't match the
     * current value — so only the most recently sent link ever works.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_verification_token')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verification_token');
        });
    }
};

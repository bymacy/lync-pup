<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Signing is done in wet ink on the printed export, so the app never
     * captured a signature image — the upload fields were removed from both
     * Information Sheet screens and nothing (exports included) reads these
     * columns. Dropping them so the schema stops implying a feature that
     * doesn't exist.
     */
    public function up(): void
    {
        Schema::table('information_sheets', function (Blueprint $table) {
            foreach (['founder_signature_path', 'director_signature_path'] as $column) {
                if (Schema::hasColumn('information_sheets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('information_sheets', function (Blueprint $table) {
            if (! Schema::hasColumn('information_sheets', 'founder_signature_path')) {
                $table->string('founder_signature_path')->nullable()->after('membership_associations');
            }

            if (! Schema::hasColumn('information_sheets', 'director_signature_path')) {
                $table->string('director_signature_path')->nullable()->after('endorsement_date');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * trl_score/mrl_score/tmrl_score/srl_score were unsignedTinyInteger (whole
 * numbers only) because the score used to be a plain count of levels with
 * at least one checked criterion. Scoring is now a weighted fraction of
 * checked criteria per level (e.g. 6.3), so these columns need to hold a
 * decimal. Raw SQL is used instead of Schema::table()->change() since this
 * project doesn't have doctrine/dbal installed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        foreach (['trl_score', 'mrl_score', 'tmrl_score', 'srl_score'] as $column) {
            if ($driver === 'sqlite') {
                // SQLite has no rigid column typing (type affinity only) — an
                // existing INTEGER column already accepts a REAL value like
                // 6.3 without a schema change, so there's nothing to alter.
                continue;
            }

            DB::statement("ALTER TABLE readiness_level_assessments MODIFY {$column} DECIMAL(3,1) UNSIGNED NULL");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        foreach (['trl_score', 'mrl_score', 'tmrl_score', 'srl_score'] as $column) {
            if ($driver === 'sqlite') {
                continue;
            }

            DB::statement("ALTER TABLE readiness_level_assessments MODIFY {$column} TINYINT UNSIGNED NULL");
        }
    }
};

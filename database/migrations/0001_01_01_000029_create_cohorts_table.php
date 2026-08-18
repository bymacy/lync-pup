<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backs the "Assign to Cohort" dropdown on the Founder Application
     * approval flow. Startups already had a plain cohort_number integer
     * (see 0001_01_01_000004_create_startups_table.php) with no management
     * UI behind it — this table gives admins a real, manageable list of
     * cohorts instead of typing a raw number.
     */
    public function up(): void
    {
        Schema::create('cohorts', function (Blueprint $table) {
            $table->id('cohort_id');
            $table->unsignedInteger('number')->unique();
            $table->string('label', 100)->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });

        // Seed cohorts 1-5 so the dropdown isn't empty out of the box and
        // lines up with the cohort_number values already used by seeded/
        // existing startups (StartupFactory/AssessmentHubSeeder/DevDataSeeder
        // all use numbers 1-5).
        $now = now();
        DB::table('cohorts')->insert(
            collect(range(1, 5))->map(fn ($n) => [
                'number' => $n,
                'label' => "Cohort {$n}",
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('cohorts');
    }
};

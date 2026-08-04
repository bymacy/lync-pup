<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roadblocks', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('roadblocks', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Scheduled', 'Resolved', 'Failed'])
                ->default('Pending')
                ->after('problem_category_other');

            $table->foreignId('mentor_id')->nullable()->after('status')
                ->constrained('mentors', 'mentor_id')->nullOnDelete();
            $table->date('meeting_date')->nullable()->after('mentor_id');
            $table->time('meeting_start_time')->nullable()->after('meeting_date');
            $table->time('meeting_end_time')->nullable()->after('meeting_start_time');
            $table->string('meeting_platform')->nullable()->after('meeting_end_time');
            $table->string('meeting_link')->nullable()->after('meeting_platform');
            $table->timestamp('failed_at')->nullable()->after('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('roadblocks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mentor_id');
            $table->dropColumn([
                'meeting_date',
                'meeting_start_time',
                'meeting_end_time',
                'meeting_platform',
                'meeting_link',
                'failed_at',
            ]);
            $table->dropColumn('status');
        });

        Schema::table('roadblocks', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Resolved'])->default('Pending')->after('problem_category_other');
        });
    }
};

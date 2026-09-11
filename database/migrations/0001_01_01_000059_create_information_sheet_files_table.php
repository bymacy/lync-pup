<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backs the Information Sheet's new "Add Supporting Documents" section — an
 * optional, multi-file upload the founder can attach to their own sheet.
 * Deliberately its own table (one row per file), mirroring
 * roadblock_files/RoadblockFile rather than a JSON column, since that's the
 * app's established pattern for this shape of "several attachments, each
 * independently removable" data (see StartupReference/TeamMember for the
 * same one-row-per-entry idea applied to non-file rows).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('information_sheet_files', function (Blueprint $table) {
            $table->id('information_sheet_file_id');
            $table->foreignId('info_sheet_id')->constrained('information_sheets', 'info_sheet_id')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_filename');
            $table->boolean('is_image')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('information_sheet_files');
    }
};

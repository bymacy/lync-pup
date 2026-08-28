<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backs the Assessment Hub's "Export Document" feature — one row per
     * generated export (a PDF bundle, a ZIP of individual PDFs, or a set of
     * individual PDFs treated as one logical export for listing/deletion
     * purposes). The actual file(s) live on the public disk under
     * `exports/{startup_id}/...`; this table is just the catalog shown on
     * the Reports tab.
     */
    public function up(): void
    {
        Schema::create('saved_reports', function (Blueprint $table) {
            $table->id('saved_report_id');
            $table->foreignId('startup_id')->constrained('startups', 'startup_id')->cascadeOnDelete();
            $table->string('file_name');

            // Relative path(s) on the 'public' disk. A PDF Bundle or single
            // Individual PDF has exactly one path; a ZIP Archive also has
            // exactly one (the .zip itself). "Individual PDFs" as a batch of
            // separate files stores one row per file, grouped by
            // export_batch so they can still be deleted/downloaded together.
            $table->string('file_path');
            $table->uuid('export_batch');

            $table->enum('format', ['PDF Bundle', 'ZIP Archive', 'Individual PDFs']);
            $table->json('document_numbers'); // e.g. [1, 2, 6, 13]
            $table->unsignedInteger('page_count')->default(0);
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->string('generated_by')->nullable(); // admin's name/email snapshot
            $table->timestamps();

            $table->index(['startup_id', 'export_batch']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_reports');
    }
};

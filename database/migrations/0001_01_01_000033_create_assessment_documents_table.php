<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Holds the free-form "Document N" forms nested under a stage tab (right
     * now: Document 6/7/8 under Active-Assessment) — each is structurally
     * very different from the others (repeating log tables, checkbox
     * grids, 5-point rating tables), so rather than modeling every field as
     * its own column, the whole document's field values are kept as one
     * JSON blob per (startup, stage, document_number).
     */
    public function up(): void
    {
        Schema::create('assessment_documents', function (Blueprint $table) {
            $table->id('assessment_document_id');
            $table->foreignId('startup_id')->constrained('startups', 'startup_id')->cascadeOnDelete();
            $table->string('stage', 30);
            $table->unsignedTinyInteger('document_number');
            $table->json('data')->nullable();
            $table->timestamps();

            $table->unique(['startup_id', 'stage', 'document_number'], 'assessment_documents_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_documents');
    }
};

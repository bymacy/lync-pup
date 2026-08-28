<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SavedReport extends Model
{
    use HasFactory;

    protected $primaryKey = 'saved_report_id';

    protected $fillable = [
        'startup_id', 'file_name', 'file_path', 'export_batch',
        'format', 'document_numbers', 'page_count', 'file_size_bytes', 'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'document_numbers' => 'array',
        ];
    }

    public function startup()
    {
        return $this->belongsTo(Startup::class, 'startup_id', 'startup_id');
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getFileSizeLabelAttribute(): string
    {
        $bytes = $this->file_size_bytes;

        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1).' MB';
        }

        return round(max($bytes, 1) / 1024, 1).' KB';
    }
}

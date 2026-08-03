<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RoadblockFile extends Model
{
    use HasFactory;

    protected $primaryKey = 'roadblock_file_id';

    protected $fillable = [
        'roadblock_id',
        'file_path',
        'original_filename',
        'is_image',
    ];

    protected $casts = [
        'is_image' => 'boolean',
    ];

    public function roadblock()
    {
        return $this->belongsTo(Roadblock::class, 'roadblock_id', 'roadblock_id');
    }

    public function getExtensionAttribute(): string
    {
        return strtoupper(pathinfo($this->original_filename, PATHINFO_EXTENSION));
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
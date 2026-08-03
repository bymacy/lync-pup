<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Roadblock extends Model
{
    use HasFactory;

    protected $primaryKey = 'roadblock_id';

    protected $fillable = [
        'startup_id',
        'problem_category',
        'problem_category_other',
        'description',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function startup()
    {
        return $this->belongsTo(Startup::class, 'startup_id', 'startup_id');
    }

    public function files()
    {
        return $this->hasMany(RoadblockFile::class, 'roadblock_id', 'roadblock_id');
    }

    public function isResolved(): bool
    {
        return $this->status === 'Resolved';
    }

    public function getDisplayCategoryAttribute(): string
    {
        return $this->problem_category === 'Others' && $this->problem_category_other
            ? $this->problem_category_other
            : $this->problem_category;
    }
}

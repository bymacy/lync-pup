<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mentor extends Model
{
    use HasFactory;

    protected $primaryKey = 'mentor_id';

    protected $fillable = [
        'honorific', 'first_name', 'last_name', 'full_name',
        'specialization', 'contact_email', 'contact_number', 'organization', 'mentor_photo_path',
    ];

    public function getDisplayNameAttribute(): string
    {
        return trim("{$this->honorific}{$this->last_name}");
    }

    public function roadblocks()
    {
        return $this->hasMany(\App\Models\Roadblock::class, 'mentor_id', 'mentor_id');
    }

    public function getCasesCountAttribute(): int
    {
        return $this->roadblocks()->whereIn('status', ['Scheduled', 'Resolved', 'Failed'])->count();
    }
}

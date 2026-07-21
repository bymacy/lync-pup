<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coordinator extends Model
{
    use HasFactory;

    protected $primaryKey = 'coordinator_id';

    protected $fillable = [
        'honorific', 'first_name', 'last_name', 'name',
        'role_title', 'email', 'phone', 'coordinator_photo_path', 'assigned_startups_count',
    ];

    public function assignments()
    {
        return $this->hasMany(CoordinatorAssignment::class, 'coordinator_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return trim("{$this->honorific} {$this->last_name}");
    }
}
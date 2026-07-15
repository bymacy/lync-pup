<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coordinator extends Model
{
    use HasFactory;

    protected $primaryKey = 'coordinator_id';

    protected $fillable = ['name', 'role_title', 'email', 'phone', 'assigned_startups_count'];

    public function assignments()
    {
        return $this->hasMany(CoordinatorAssignment::class, 'coordinator_id');
    }
}
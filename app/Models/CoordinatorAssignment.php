<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoordinatorAssignment extends Model
{
    use HasFactory;

    protected $primaryKey = 'assignment_id';

    protected $fillable = ['startup_id', 'coordinator_id', 'assigned_date', 'assignment_status'];

    protected function casts(): array
    {
        return ['assigned_date' => 'date'];
    }

    public function startup()
    {
        return $this->belongsTo(Startup::class, 'startup_id');
    }

    public function coordinator()
    {
        return $this->belongsTo(Coordinator::class, 'coordinator_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use HasFactory;

    protected $primaryKey = 'member_id';

    protected $fillable = ['startup_id', 'full_name', 'role', 'email'];

    public function startup()
    {
        return $this->belongsTo(Startup::class, 'startup_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use HasFactory;

    protected $primaryKey = 'member_id';

    protected $fillable = ['startup_id', 'full_name', 'designation', 'role', 'phone', 'address', 'date_of_birth', 'email', 'citizenship', 'sex', 'civil_status'];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function startup()
    {
        return $this->belongsTo(Startup::class, 'startup_id');
    }
}
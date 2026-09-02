<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The Startup Profile page's own Core Team roster - separate from
 * TeamMember, which belongs to the Information Sheet (see migration
 * 000049). Deliberately minimal: the Profile page only ever asks for a
 * name, unlike TeamMember's full biographical set.
 */
class StartupTeamMember extends Model
{
    use HasFactory;

    protected $primaryKey = 'startup_team_member_id';

    protected $fillable = ['startup_id', 'full_name'];

    public function startup()
    {
        return $this->belongsTo(Startup::class, 'startup_id');
    }
}

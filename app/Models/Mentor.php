<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mentor extends Model
{
    use HasFactory;

    protected $primaryKey = 'mentor_id';

    protected $fillable = [
        'honorific',
        'first_name',
        'last_name',
        'full_name',
        'specialization',
        'contact_email',
        'contact_number',
        'organization',
        'mentor_photo_path',
    ];

    public function getDisplayNameAttribute(): string
    {
        return collect([$this->honorific, $this->last_name])
            ->filter()
            ->implode(' ');
    }

    public function roadblocks()
    {
        return $this->hasMany(\App\Models\Roadblock::class, 'mentor_id', 'mentor_id');
    }

    public function getCasesCountAttribute(): int
    {
        return $this->roadblocks()->whereIn('status', ['Scheduled', 'Pending Review', 'Resolved', 'Failed'])->count();
    }

    /**
     * Still assigned and not yet closed out — Scheduled (upcoming meeting)
     * or Pending Review (meeting happened, awaiting Resolved/Failed).
     */
    public function getActiveCasesCountAttribute(): int
    {
        return $this->roadblocks()->whereIn('status', ['Scheduled', 'Pending Review'])->count();
    }

    /**
     * Closed out either way — Resolved or Failed. "Completed" here means
     * the case reached a final outcome, not that it succeeded.
     */
    public function getCompletedCasesCountAttribute(): int
    {
        return $this->roadblocks()->whereIn('status', ['Resolved', 'Failed'])->count();
    }
}

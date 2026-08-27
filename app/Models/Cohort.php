<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cohort extends Model
{
    use HasFactory;

    protected $primaryKey = 'cohort_id';

    protected $fillable = ['number', 'label', 'start_date', 'end_date', 'description', 'status'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function startups()
    {
        return $this->hasMany(Startup::class, 'cohort_id', 'cohort_id');
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: "Cohort {$this->number}";
    }

    /**
     * The stored `status` value is still the original 'Active'/'Inactive'
     * enum (see migration 0001_01_01_000035's docblock) — "Archived" is
     * purely how 'Inactive' is presented in the admin Dashboard's cohort
     * dropdown and modals, without touching the underlying DB constraint.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'Inactive' ? 'Archived' : 'Active';
    }

    public function isArchived(): bool
    {
        return $this->status === 'Inactive';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Startup extends Model
{
    use HasFactory;

    protected $primaryKey = 'startup_id';

    protected $fillable = [
        'user_id',
        'company_name',
        'industry_sector',
        'cohort_number',
        'contact_phone',
        'location',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function informationSheet()
    {
        return $this->hasOne(InformationSheet::class, 'startup_id');
    }

    public function teamMembers()
    {
        return $this->hasMany(TeamMember::class, 'startup_id');
    }

    public function readinessAssessments()
    {
        return $this->hasMany(ReadinessLevelAssessment::class, 'startup_id');
    }

    public function latestReadinessAssessment()
    {
        return $this->hasOne(ReadinessLevelAssessment::class, 'startup_id')->latestOfMany('assessment_date');
    }

    public function coordinatorAssignments()
    {
        return $this->hasMany(CoordinatorAssignment::class, 'startup_id');
    }

    public function activeCoordinatorAssignment()
    {
        return $this->hasOne(CoordinatorAssignment::class, 'startup_id')->where('assignment_status', 'Active');
    }

    // Computed status, not stored
    public function getStatusAttribute(): string
    {
        $sheet = $this->informationSheet;

        if (! $sheet || $sheet->approval_status === 'Pending') {
            return 'Pending';
        }

        if ($sheet->approval_status === 'Rejected') {
            return 'Rejected';
        }

        return $this->activeCoordinatorAssignment ? 'Active' : 'Assign Coordinator';
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereHas('informationSheet', fn ($q) => $q->where('approval_status', 'Pending'))
            ->orWhereDoesntHave('informationSheet');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('informationSheet', fn ($q) => $q->where('approval_status', 'Approved'))
            ->whereHas('activeCoordinatorAssignment');
    }

    public function scopeNeedsCoordinator(Builder $query): Builder
    {
        return $query->whereHas('informationSheet', fn ($q) => $q->where('approval_status', 'Approved'))
            ->whereDoesntHave('activeCoordinatorAssignment');
    }
}
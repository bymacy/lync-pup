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
        'user_id', 'company_name', 'industry_sector', 'cohort_number',
        'contact_phone', 'location', 'website', 'startup_photo_path', 'pitch_deck_requested_at',
    ];

    protected function casts(): array
    {
        return ['pitch_deck_requested_at' => 'datetime'];
    }

    public function getBatchLabelAttribute(): string
    {
        return "Cohort {$this->cohort_number}";
    }

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
    
    public function roadblocks()
    {   
    return $this->hasMany(Roadblock::class, 'startup_id', 'startup_id');
    }

    public function evaluationSchedules()
    {
        return $this->hasMany(EvaluationSchedule::class, 'startup_id', 'startup_id');
    }

    public function latestEvaluationSchedule()
    {
        return $this->hasOne(EvaluationSchedule::class, 'startup_id', 'startup_id')->latestOfMany('evaluation_date');
    }

    public function getEvaluationStatusAttribute(): string
    {
        $latest = $this->latestEvaluationSchedule;

        if (! $latest) {
            return 'Not Started';
        }

        return $latest->status === 'Completed' ? 'Completed' : 'In Progress';
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
        return $query->where(function ($q) {
            $q->whereHas('informationSheet', fn ($q2) => $q2->where('approval_status', 'Pending'))
                ->orWhereDoesntHave('informationSheet');
        });
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
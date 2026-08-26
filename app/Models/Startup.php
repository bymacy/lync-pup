<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Startup extends Model
{
    use HasFactory;

    protected $primaryKey = 'startup_id';

    protected $fillable = [
        'user_id', 'company_name', 'industry_sector', 'cohort_number',
        'contact_phone', 'location', 'website', 'startup_photo_path', 'pitch_deck_requested_at',
        'cohort_id', 'admin_remarks', 'rejection_reason', 'application_decided_at',
    ];

    protected function casts(): array
    {
        return [
            'pitch_deck_requested_at' => 'datetime',
            'application_decided_at' => 'datetime',
        ];
    }

    public function getBatchLabelAttribute(): string
    {
        return "Cohort {$this->cohort_number}";
    }

    /**
     * Public URL for the uploaded startup logo/photo, or null when none has
     * been uploaded — callers (e.g. the mentorship table's avatar) fall back
     * to an initials badge in that case.
     */
    public function getStartupPhotoUrlAttribute(): ?string
    {
        return $this->startup_photo_path
            ? Storage::disk('public')->url($this->startup_photo_path)
            : null;
    }

    /**
     * Synthetic reference shown on the Founder Application review/view
     * screens, e.g. "APP-2026-00031". Not stored — derived from the
     * registration year and the startup's own primary key.
     */
    public function getApplicationIdAttribute(): string
    {
        $year = $this->created_at?->format('Y') ?? now()->format('Y');

        return 'APP-'.$year.'-'.str_pad((string) $this->startup_id, 5, '0', STR_PAD_LEFT);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cohort()
    {
        return $this->belongsTo(Cohort::class, 'cohort_id', 'cohort_id');
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

    /**
     * True once an admin has booked (and not cancelled) an evaluation for
     * this startup. Once this is true, the founder's/admin's Information
     * Sheet save should stop treating blank fields as "clear this value" —
     * see InformationSheet::blankedFields().
     */
    public function hasScheduledEvaluation(): bool
    {
        return $this->evaluationSchedules()->where('status', '!=', 'Cancelled')->exists();
    }

    /**
     * True from the calendar day of a (non-cancelled) evaluation onward —
     * drives the founder-side Information Sheet lock. Admin retains edit
     * access so revisions can still be made during/around the evaluation.
     */
    public function evaluationDayLockActive(): bool
    {
        return $this->evaluationSchedules()
            ->where('status', '!=', 'Cancelled')
            ->whereDate('evaluation_date', '<=', now()->toDateString())
            ->exists();
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

    /**
     * Scopes below drive the admin "Founder Application" screen. They key
     * off the founder's account_status (users.account_status), which is
     * distinct from the informationSheet approval_status used by the
     * scopes above — a founder's account can be Pending/Active/Rejected
     * long before they ever fill out an information sheet.
     */
    public function scopeApplicationPending(Builder $query): Builder
    {
        return $query->whereHas('user', fn ($q) => $q->where('account_status', 'Pending'));
    }

    public function scopeApplicationApproved(Builder $query): Builder
    {
        return $query->whereHas('user', fn ($q) => $q->where('account_status', 'Active'));
    }

    public function scopeApplicationRejected(Builder $query): Builder
    {
        return $query->whereHas('user', fn ($q) => $q->where('account_status', 'Rejected'));
    }
}
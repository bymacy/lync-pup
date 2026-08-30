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

    public function savedReports()
    {
        return $this->hasMany(SavedReport::class, 'startup_id', 'startup_id');
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
     * True only on the calendar day of a (non-cancelled) evaluation — drives
     * the founder-side Information Sheet lock, so the sheet is frozen while
     * the evaluators are reading it and reopens by itself the next morning.
     *
     * Deliberately the day itself and not "from the day onward": an
     * evaluation nobody attended used to lock the founder out permanently,
     * with no path back except a coordinator noticing the Missed tab. A
     * missed evaluation now simply releases the sheet.
     *
     * Admin retains edit access throughout, and an Approved sheet stays
     * locked on its own separate check.
     */
    public function evaluationDayLockActive(): bool
    {
        return $this->evaluationSchedules()
            ->where('status', '!=', 'Cancelled')
            ->whereDate('evaluation_date', now()->toDateString())
            ->exists();
    }

    // Computed status, not stored
    public function getStatusAttribute(): string
    {
        $sheet = $this->informationSheet;

        if ($sheet && $sheet->approval_status === 'Rejected') {
            return 'Rejected';
        }

        if ($sheet && $sheet->approval_status === 'Approved') {
            return $this->activeCoordinatorAssignment ? 'Active' : 'Assign Coordinator';
        }

        // Not yet decided (no sheet, or approval_status still 'Pending'):
        // per direct testing feedback, split into Onboarding vs Pending
        // based on whether the startup has actually finished Profile Setup
        // + the Information Sheet AND been scheduled for evaluation.
        return $this->isReadyForEvaluation() ? 'Pending' : 'Onboarding';
    }

    /**
     * True once the Startup Profile itself (company name, founder name,
     * industry, location, phone, photo) has all of its required fields
     * filled in — the gate for the founder-side Information Sheet module
     * (see InformationSheetController) and the first step of the founder
     * dashboard's "Startup Onboarding" tracker. Deliberately excludes the
     * Information Sheet's own fields (business_description, submission_date)
     * since those belong to the module this gates, not the profile itself.
     */
    public function isProfileComplete(): bool
    {
        return filled($this->company_name)
            && filled($this->industry_sector)
            && filled($this->user?->name)
            && filled($this->contact_phone)
            && filled($this->location)
            && filled($this->startup_photo_path);
    }

    /**
     * True once a startup has completed Profile Setup (industry, location,
     * phone, photo) and the Information Sheet (business description +
     * actually submitted, not just saved), AND has a non-cancelled
     * evaluation scheduled. Backs both the 'Pending' branch of the status
     * accessor above and scopeAwaitingEvaluation() below — kept as one
     * source of truth so the card badge and the tab filter never disagree.
     */
    protected function isReadyForEvaluation(): bool
    {
        $sheet = $this->informationSheet;

        $profileComplete = $this->isProfileComplete()
            && $sheet
            && filled($sheet->business_description)
            && filled($sheet->submission_date);

        return $profileComplete && $this->hasScheduledEvaluation();
    }

    /**
     * Broader "Pending" used by the Assessment Hub's "Awaiting Schedule"
     * list — any startup not yet approved/rejected, including ones with no
     * Information Sheet at all. Left as-is (not narrowed to match the
     * Startup Profile page's stricter 'Pending' tab, see
     * scopeAwaitingEvaluation()) since AssessmentHubController relies on
     * this exact broad definition and then filters scheduling separately.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereHas('informationSheet', fn ($q2) => $q2->where('approval_status', 'Pending'))
                ->orWhereDoesntHave('informationSheet');
        });
    }

    /**
     * "Onboarding" tab on the Startup Profile page — not yet approved or
     * rejected, and NOT (yet) ready for evaluation per isReadyForEvaluation()
     * above: still missing Profile Setup fields, hasn't submitted the
     * Information Sheet, or hasn't been scheduled for evaluation yet.
     */
    public function scopeOnboarding(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('informationSheet', fn ($q) => $q->whereIn('approval_status', ['Approved', 'Rejected']))
            ->where(function ($q) {
                $q->whereNull('industry_sector')->orWhere('industry_sector', '')
                    ->orWhereNull('location')->orWhere('location', '')
                    ->orWhereNull('contact_phone')->orWhere('contact_phone', '')
                    ->orWhereNull('startup_photo_path')->orWhere('startup_photo_path', '')
                    ->orWhereDoesntHave('informationSheet', fn ($q2) => $q2
                        ->whereNotNull('business_description')->where('business_description', '!=', '')
                        ->whereNotNull('submission_date'))
                    ->orWhereDoesntHave('evaluationSchedules', fn ($q2) => $q2->where('status', '!=', 'Cancelled'));
            });
    }

    /**
     * "Pending" tab on the Startup Profile page — the exact inverse of
     * scopeOnboarding() within the not-yet-decided pool: Profile Setup and
     * the Information Sheet are both complete, and an evaluation has been
     * scheduled. Distinct from the broader scopePending() above, which the
     * Assessment Hub still relies on.
     */
    public function scopeAwaitingEvaluation(Builder $query): Builder
    {
        return $query
            ->whereDoesntHave('informationSheet', fn ($q) => $q->whereIn('approval_status', ['Approved', 'Rejected']))
            ->whereNotNull('industry_sector')->where('industry_sector', '!=', '')
            ->whereNotNull('location')->where('location', '!=', '')
            ->whereNotNull('contact_phone')->where('contact_phone', '!=', '')
            ->whereNotNull('startup_photo_path')->where('startup_photo_path', '!=', '')
            ->whereHas('informationSheet', fn ($q) => $q
                ->whereNotNull('business_description')->where('business_description', '!=', '')
                ->whereNotNull('submission_date'))
            ->whereHas('evaluationSchedules', fn ($q) => $q->where('status', '!=', 'Cancelled'));
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
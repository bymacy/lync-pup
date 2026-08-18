<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Roadblock extends Model
{
    use HasFactory;

    protected $primaryKey = 'roadblock_id';

    protected $fillable = [
        'startup_id',
        'problem_category',
        'problem_category_other',
        'description',
        'status',
        'mentor_id',
        'meeting_date',
        'meeting_start_time',
        'meeting_end_time',
        'meeting_platform',
        'meeting_link',
        'resolved_at',
        'failed_at',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'resolved_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function startup()
    {
        return $this->belongsTo(Startup::class, 'startup_id', 'startup_id');
    }

    public function files()
    {
        return $this->hasMany(RoadblockFile::class, 'roadblock_id', 'roadblock_id');
    }

    public function mentor()
    {
        return $this->belongsTo(Mentor::class, 'mentor_id', 'mentor_id');
    }

    public function isResolved(): bool
    {
        return $this->status === 'Resolved';
    }

    public function getDisplayCategoryAttribute(): string
    {
        return $this->problem_category === 'Others' && $this->problem_category_other
            ? $this->problem_category_other
            : $this->problem_category;
    }

    public function getMeetingStartsAtAttribute(): ?Carbon
    {
        if (!$this->meeting_date || !$this->meeting_start_time) {
            return null;
        }

        return Carbon::parse($this->meeting_date->format('Y-m-d') . ' ' . $this->meeting_start_time);
    }

    public function getMeetingEndsAtAttribute(): ?Carbon
    {
        if (!$this->meeting_date || !$this->meeting_end_time) {
            return null;
        }

        return Carbon::parse($this->meeting_date->format('Y-m-d') . ' ' . $this->meeting_end_time);
    }

    /**
     * True once the roadblock is awaiting the admin's Resolved/Failed call —
     * either because it's already been promoted to the real "Pending Review"
     * status, or (transitionally, before the next sweep catches it) it's
     * still "Scheduled" but its meeting time has already passed.
     */
    public function isInAssessment(): bool
    {
        return $this->status === 'Pending Review'
            || ($this->status === 'Scheduled' && $this->meeting_ends_at && $this->meeting_ends_at->isPast());
    }

    /**
     * Sweep every Scheduled roadblock whose meeting has concluded and move it
     * to "Pending Review" so the status column always reflects reality by the
     * time anyone loads a page that lists roadblocks. The app has no cron/job
     * scheduler, so this runs lazily at the top of the relevant controllers
     * instead of on a timer.
     */
    public static function promoteEndedMeetingsToPendingReview(): void
    {
        $idsToPromote = static::where('status', 'Scheduled')
            ->whereNotNull('meeting_date')
            ->whereNotNull('meeting_end_time')
            ->get(['roadblock_id', 'meeting_date', 'meeting_end_time'])
            ->filter(fn (self $r) => $r->meeting_ends_at && $r->meeting_ends_at->isPast())
            ->pluck('roadblock_id');

        if ($idsToPromote->isNotEmpty()) {
            static::whereIn('roadblock_id', $idsToPromote)->update(['status' => 'Pending Review']);
        }
    }

    /**
     * True right now, between the meeting's start and end time (inclusive).
     * This is the single source of truth for "is this meeting live" — used
     * to switch the admin Edit button to Join, and to gate the founder-side
     * Join Meeting button.
     */
    public function isLive(): bool
    {
        return $this->meeting_starts_at && $this->meeting_ends_at
            && now()->between($this->meeting_starts_at, $this->meeting_ends_at);
    }

    public function getMeetingStatusLabelAttribute(): string
    {
        if (!$this->meeting_date) {
            return '';
        }

        if ($this->isLive()) {
            return 'Live (In-Session)';
        }

        if ($this->meeting_date->isToday()) {
            return 'Scheduled (Today)';
        }

        if ($this->meeting_date->isTomorrow()) {
            return 'Soon (Tomorrow)';
        }

        return 'Upcoming (' . $this->meeting_date->format('M j') . ')';
    }

    /**
     * "8:00 AM - 9:00 AM" — 12-hour formatted meeting time range.
     */
    public function getMeetingTimeRangeLabelAttribute(): ?string
    {
        if (! $this->meeting_start_time || ! $this->meeting_end_time) {
            return null;
        }

        return Carbon::parse($this->meeting_start_time)->format('g:i A')
            .' - '.Carbon::parse($this->meeting_end_time)->format('g:i A');
    }
}

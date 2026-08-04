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

    public function isInAssessment(): bool
    {
        return $this->status === 'Scheduled' && $this->meeting_ends_at && $this->meeting_ends_at->isPast();
    }

    public function getMeetingStatusLabelAttribute(): string
    {
        if (!$this->meeting_date) {
            return '';
        }

        if ($this->meeting_starts_at && $this->meeting_ends_at && now()->between($this->meeting_starts_at, $this->meeting_ends_at)) {
            return 'Live (In-Session)';
        }

        if ($this->meeting_date->isToday()) {
            return 'Scheduled (Today)';
        }

        if ($this->meeting_date->isTomorrow()) {
            return 'Upcoming (Tomorrow)';
        }

        return 'Soon (' . $this->meeting_date->format('M j') . ')';
    }
}

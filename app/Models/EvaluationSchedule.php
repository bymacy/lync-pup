<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class EvaluationSchedule extends Model
{
    use HasFactory;

    protected $primaryKey = 'evaluation_schedule_id';

    protected $fillable = [
        'startup_id',
        'evaluation_date',
        'start_time',
        'end_time',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'evaluation_date' => 'date',
        ];
    }

    /**
     * Fixed evaluation-day time slots (start, end) in 24h format.
     * Kept as a single source of truth for both the UI and server-side validation.
     */
    public const TIME_SLOTS = [
        ['08:00', '09:00'],
        ['09:00', '10:00'],
        ['10:00', '11:00'],
        ['11:00', '12:00'],
        ['13:00', '14:00'],
        ['14:00', '15:00'],
        ['15:00', '16:00'],
    ];

    public function startup()
    {
        return $this->belongsTo(Startup::class, 'startup_id', 'startup_id');
    }

    public function getEndsAtAttribute(): ?Carbon
    {
        if (! $this->evaluation_date || ! $this->end_time) {
            return null;
        }

        return Carbon::parse($this->evaluation_date->format('Y-m-d').' '.$this->end_time);
    }

    public function isToday(): bool
    {
        return $this->evaluation_date->isToday();
    }

    /**
     * A schedule is "missed" once its date has passed and nobody
     * marked it Completed (or Cancelled) in the meantime.
     */
    public function isMissed(): bool
    {
        return $this->status === 'Scheduled' && $this->evaluation_date->lt(now()->startOfDay());
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'Scheduled' && $this->evaluation_date->gt(now()->startOfDay());
    }

    public function getTimeRangeLabelAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('g:i A').' - '.Carbon::parse($this->end_time)->format('g:i A');
    }
}

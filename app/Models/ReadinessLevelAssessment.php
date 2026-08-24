<?php

namespace App\Models;

use App\Support\ReadinessRubric;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadinessLevelAssessment extends Model
{
    use HasFactory;

    protected $primaryKey = 'assessment_id';

    protected $fillable = [
        'startup_id', 'stage', 'evaluated_by',
        'trl_score', 'trl_progress', 'trl_overview',
        'mrl_score', 'mrl_progress',
        'tmrl_score', 'tmrl_progress',
        'srl_score', 'srl_progress',
        'overall_score', 'remarks', 'assessment_date',
    ];

    protected function casts(): array
    {
        return [
            'assessment_date' => 'date',
            'trl_progress' => 'array',
            'trl_overview' => 'array',
            'mrl_progress' => 'array',
            'tmrl_progress' => 'array',
            'srl_progress' => 'array',
            'trl_score' => 'integer',
            'mrl_score' => 'integer',
            'tmrl_score' => 'integer',
            'srl_score' => 'integer',
            'overall_score' => 'float',
        ];
    }

    public function startup()
    {
        return $this->belongsTo(Startup::class, 'startup_id');
    }

    /**
     * Column-name helpers so controller/view code can loop over
     * ReadinessRubric::TYPES ('TRL', 'MRL', ...) instead of hardcoding four
     * near-identical branches — e.g. $assessment->progressFor('TRL') instead
     * of $assessment->trl_progress.
     */
    public function progressFor(string $type): array
    {
        return $this->{strtolower($type).'_progress'} ?? [];
    }

    public function scoreFor(string $type): ?int
    {
        return $this->{strtolower($type).'_score'};
    }

    /**
     * Recomputes every *_score column from its matching *_progress column
     * (via ReadinessRubric::scoreFromProgress) and overall_score as their
     * average — called right before saving, so the stored score columns
     * (read everywhere else in the app: readiness-radar, "RLS X.X" badges,
     * etc.) always agree with the checked criteria that produced them.
     */
    public function recomputeScores(): static
    {
        $scores = [];

        foreach (ReadinessRubric::TYPES as $type) {
            $score = ReadinessRubric::scoreFromProgress($type, $this->progressFor($type));
            $this->{strtolower($type).'_score'} = $score;
            $scores[] = $score;
        }

        $known = array_filter($scores, fn ($s) => $s !== null);
        $this->overall_score = $known ? round(array_sum($known) / count($scores), 1) : null;

        return $this;
    }
}

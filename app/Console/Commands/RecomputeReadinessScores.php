<?php

namespace App\Console\Commands;

use App\Models\ReadinessLevelAssessment;
use Illuminate\Console\Command;

class RecomputeReadinessScores extends Command
{
    /**
     * Scoring changed from a whole-number count ("this level has at least
     * one checkbox checked") to a weighted decimal (sum, across all 9
     * levels, of checked criteria ÷ total criteria per level — see
     * ReadinessRubric::scoreFromProgress()). Every assessment saved before
     * that change still has its old whole-number scores sitting in the
     * trl_score/mrl_score/tmrl_score/srl_score/overall_score columns; this
     * command re-derives them from each row's own stored *_progress JSON
     * (the checkboxes themselves never changed) so old and new assessments
     * read consistently everywhere the score is shown.
     *
     * Dry-run by default (just reports how many rows would change) — pass
     * --force to actually save the recomputed scores.
     */
    protected $signature = 'readiness:recompute-scores {--force : Actually save the recomputed scores instead of just reporting them}';

    protected $description = 'Recompute every saved assessment\'s TRL/MRL/TMRL/SRL/overall scores from its stored progress checklist under the current (weighted decimal) formula';

    public function handle(): int
    {
        $assessments = ReadinessLevelAssessment::all();

        if ($assessments->isEmpty()) {
            $this->info('No readiness assessments found.');

            return self::SUCCESS;
        }

        $changed = [];

        foreach ($assessments as $assessment) {
            $before = [
                'trl' => $assessment->trl_score,
                'mrl' => $assessment->mrl_score,
                'tmrl' => $assessment->tmrl_score,
                'srl' => $assessment->srl_score,
                'overall' => $assessment->overall_score,
            ];

            $assessment->recomputeScores();

            $after = [
                'trl' => $assessment->trl_score,
                'mrl' => $assessment->mrl_score,
                'tmrl' => $assessment->tmrl_score,
                'srl' => $assessment->srl_score,
                'overall' => $assessment->overall_score,
            ];

            if ($before !== $after) {
                $changed[] = [
                    $assessment->assessment_id,
                    $assessment->startup_id,
                    $assessment->stage,
                    $this->diffLabel($before, $after),
                ];

                if ($this->option('force')) {
                    $assessment->save();
                }
            }
        }

        if (empty($changed)) {
            $this->info('All '.$assessments->count().' assessment(s) already match the current scoring formula — nothing to do.');

            return self::SUCCESS;
        }

        $this->table(['Assessment ID', 'Startup ID', 'Stage', 'Scores (old → new)'], $changed);

        if (! $this->option('force')) {
            $this->warn(count($changed).' assessment(s) above would be recomputed. Re-run with --force to save them.');

            return self::SUCCESS;
        }

        $this->info(count($changed).' assessment(s) recomputed and saved.');

        return self::SUCCESS;
    }

    private function diffLabel(array $before, array $after): string
    {
        $parts = [];

        foreach ($before as $key => $oldValue) {
            $newValue = $after[$key];

            if ($oldValue !== $newValue) {
                $parts[] = strtoupper($key).': '.($oldValue ?? '—').' → '.($newValue ?? '—');
            }
        }

        return implode("\n", $parts);
    }
}

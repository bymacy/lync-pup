<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentDocument;
use App\Models\Startup;
use App\Support\RiskEngine;
use Illuminate\View\View;

class RiskMonitoringController extends Controller
{
    public function index(): View
    {
        $startups = Startup::with(['informationSheet', 'activeCoordinatorAssignment', 'roadblocks', 'readinessAssessments', 'cohort'])->get();

        $documentsByStartup = AssessmentDocument::whereIn('startup_id', $startups->pluck('startup_id'))
            ->get()
            ->groupBy('startup_id');

        // Assess every startup once; the result is reused for the donut
        // chart, the category breakdown table, and the risk indicator table.
        $assessments = $startups->mapWithKeys(fn (Startup $startup) => [
            $startup->startup_id => RiskEngine::assess($startup, $documentsByStartup->get($startup->startup_id)),
        ]);

        // Risk Register: how many startups fall into each overall risk level.
        $levelCounts = collect(['Critical', 'High', 'Moderate', 'Low', 'None'])
            ->mapWithKeys(fn ($level) => [
                $level => $assessments->filter(fn ($a) => $a['level'] === $level)->count(),
            ]);

        // Top Risk Categories: for each category, how many startups have at
        // least one indicator in that category triggered, broken down by
        // those startups' OVERALL risk level — shows how serious the
        // fallout tends to be for startups affected by that category.
        $categoryBreakdown = collect(RiskEngine::CATEGORIES)->map(function ($category) use ($startups, $assessments) {
            $affected = $startups->filter(
                fn (Startup $startup) => collect($assessments[$startup->startup_id]['indicators'])
                    ->contains(fn ($indicator) => $indicator['category'] === $category)
            );

            $byLevel = collect(['Critical', 'High', 'Moderate', 'Low'])
                ->mapWithKeys(fn ($level) => [
                    $level => $affected->filter(fn ($s) => $assessments[$s->startup_id]['level'] === $level)->count(),
                ]);

            return [
                'category' => $category,
                'count' => $affected->count(),
                'by_level' => $byLevel,
            ];
        });

        // Risk Indicator table: one row per startup with at least one
        // triggered indicator, highest score first. Startups with no risk
        // at all are omitted — there's nothing actionable to show.
        $riskRows = $startups
            ->filter(fn (Startup $startup) => $assessments[$startup->startup_id]['score'] > 0)
            ->map(fn (Startup $startup) => [
                'startup' => $startup,
                'assessment' => $assessments[$startup->startup_id],
            ])
            ->sortByDesc(fn ($row) => $row['assessment']['score'])
            ->values();

        return view('admin.risk-monitoring.index', [
            'totalStartups' => $startups->count(),
            'levelCounts' => $levelCounts,
            'categoryBreakdown' => $categoryBreakdown,
            'riskRows' => $riskRows,
            'levelColors' => RiskEngine::LEVEL_COLORS,
            'severityColors' => RiskEngine::SEVERITY_COLORS,
        ]);
    }
}

<?php
/**
 * One-off patch script for the Assessment Hub feature.
 * Run once from the Laravel project root: php patch_assessment_hub.php
 * Safe to re-run: every replacement is skipped (with a warning) if the
 * anchor text isn't found, instead of corrupting the file.
 */

function patch(string $path, string $search, string $replace, string $label): void
{
    if (! file_exists($path)) {
        echo "  [SKIP] $label — file not found: $path\n";
        return;
    }

    $content = file_get_contents($path);

    if (str_contains($content, $search) === false) {
        echo "  [WARN] $label — anchor text not found in $path (already patched, or file changed). Skipping.\n";
        return;
    }

    if (str_contains($content, $replace)) {
        echo "  [SKIP] $label — already applied.\n";
        return;
    }

    $count = 0;
    $new = str_replace($search, $replace, $content, $count);
    file_put_contents($path, $new);
    echo "  [OK]   $label ($count replacement(s))\n";
}

echo "Patching routes/web.php...\n";

patch(
    'routes/web.php',
    "use App\Http\Controllers\Admin\RoadblockController as AdminRoadblockController;\n",
    "use App\Http\Controllers\Admin\RoadblockController as AdminRoadblockController;\nuse App\Http\Controllers\Admin\AssessmentHubController;\nuse App\Http\Controllers\Admin\EvaluationScheduleController;\n",
    'Add Assessment Hub controller imports'
);

patch(
    'routes/web.php',
    "    Route::delete('/roadblocks/{roadblock}', [AdminRoadblockController::class, 'destroy'])->name('roadblocks.destroy');\n\n    // Future modules (Coordinator Profile, Assessment Hub, Roadblock Management, Risk Monitoring) nest here\n",
    "    Route::delete('/roadblocks/{roadblock}', [AdminRoadblockController::class, 'destroy'])->name('roadblocks.destroy');\n\n"
    ."    Route::get('/assessment-hub', [AssessmentHubController::class, 'index'])->name('assessment-hub.index');\n"
    ."    Route::post('/assessment-hub/evaluations', [EvaluationScheduleController::class, 'store'])->name('assessment-hub.evaluations.store');\n"
    ."    Route::put('/assessment-hub/evaluations/{evaluationSchedule}', [EvaluationScheduleController::class, 'update'])->name('assessment-hub.evaluations.update');\n"
    ."    Route::delete('/assessment-hub/evaluations/{evaluationSchedule}', [EvaluationScheduleController::class, 'destroy'])->name('assessment-hub.evaluations.destroy');\n\n"
    ."    // Future modules (Risk Monitoring) nest here\n",
    'Add Assessment Hub routes'
);

echo "Patching app/Models/Startup.php...\n";

patch(
    'app/Models/Startup.php',
    "    public function roadblocks()\n    {   \n    return \$this->hasMany(Roadblock::class, 'startup_id', 'startup_id');\n    }\n",
    "    public function roadblocks()\n    {   \n    return \$this->hasMany(Roadblock::class, 'startup_id', 'startup_id');\n    }\n\n"
    ."    public function evaluationSchedules()\n"
    ."    {\n"
    ."        return \$this->hasMany(EvaluationSchedule::class, 'startup_id', 'startup_id');\n"
    ."    }\n\n"
    ."    public function latestEvaluationSchedule()\n"
    ."    {\n"
    ."        return \$this->hasOne(EvaluationSchedule::class, 'startup_id', 'startup_id')->latestOfMany('evaluation_date');\n"
    ."    }\n\n"
    ."    public function getEvaluationStatusAttribute(): string\n"
    ."    {\n"
    ."        \$latest = \$this->latestEvaluationSchedule;\n\n"
    ."        if (! \$latest) {\n"
    ."            return 'Not Started';\n"
    ."        }\n\n"
    ."        return \$latest->status === 'Completed' ? 'Completed' : 'In Progress';\n"
    ."    }\n",
    'Add evaluation relations + status accessor to Startup model'
);

echo "Patching app/Http/Controllers/Admin/InformationSheetController.php...\n";

patch(
    'app/Http/Controllers/Admin/InformationSheetController.php',
    "        \$startup->informationSheet()->update([\n            'approval_status' => 'Approved',\n            'evaluator_remarks' => null,\n        ]);\n\n        return redirect()",
    "        \$startup->informationSheet()->update([\n            'approval_status' => 'Approved',\n            'evaluator_remarks' => null,\n        ]);\n\n"
    ."        \$startup->evaluationSchedules()\n"
    ."            ->where('status', 'Scheduled')\n"
    ."            ->latest('evaluation_date')\n"
    ."            ->first()\n"
    ."            ?->update(['status' => 'Completed']);\n\n"
    ."        return redirect()",
    'Mark matching evaluation schedule Completed on approval'
);

echo "Patching resources/views/admin/information-sheets/show.blade.php...\n";

patch(
    'resources/views/admin/information-sheets/show.blade.php',
    "    <div class=\"bg-white rounded-xl border border-gray-200 max-w-5xl mx-auto\">",
    "    <div class=\"bg-white rounded-xl border border-gray-200 max-w-5xl mx-auto\" x-data=\"{ approveConfirmOpen: false }\">",
    'Add Alpine state for the approve-confirmation modal'
);

patch(
    'resources/views/admin/information-sheets/show.blade.php',
    "                @if (\$startup->informationSheet?->approval_status === 'Pending')\n"
    ."                    <form method=\"POST\" action=\"{{ route('admin.information-sheet.approve', \$startup) }}\" class=\"flex-1\">\n"
    ."                        @csrf\n"
    ."                        @method('PATCH')\n"
    ."                        <button type=\"submit\" class=\"w-full bg-gradient-to-r from-[#6D0D23] to-[#11386A] hover:opacity-90 transition-all duration-200 text-white rounded-lg py-3 text-sm font-medium\">\n"
    ."                            APPROVE & LOCK\n"
    ."                        </button>\n"
    ."                    </form>\n"
    ."                @else",
    "                @if (\$startup->informationSheet?->approval_status === 'Pending')\n"
    ."                    <button type=\"button\" @click=\"approveConfirmOpen = true\" class=\"flex-1 bg-gradient-to-r from-[#6D0D23] to-[#11386A] hover:opacity-90 transition-all duration-200 text-white rounded-lg py-3 text-sm font-medium\">\n"
    ."                        APPROVE & LOCK\n"
    ."                    </button>\n\n"
    ."                    <x-confirm-action-modal\n"
    ."                        show=\"approveConfirmOpen\" close=\"approveConfirmOpen = false\"\n"
    ."                        title=\"Confirm Startup Approval\"\n"
    ."                        message=\"\"\n"
    ."                        :action=\"route('admin.information-sheet.approve', \$startup)\"\n"
    ."                        method=\"PATCH\" confirm-label=\"Confirm\" icon=\"people\" />\n"
    ."                @else",
    'Swap direct-submit Approve button for a confirm modal'
);

echo "\nDone.\n";

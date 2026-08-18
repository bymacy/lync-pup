<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCohortRequest;
use App\Http\Requests\Admin\UpdateCohortRequest;
use App\Models\Cohort;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CohortController extends Controller
{
    public function index(): View
    {
        $cohorts = Cohort::withCount('startups')->orderBy('number')->get();

        return view('admin.cohorts.index', compact('cohorts'));
    }

    public function store(StoreCohortRequest $request): RedirectResponse
    {
        Cohort::create($request->validated());

        return redirect()->route('admin.cohorts.index')->with('status', 'Cohort added successfully.');
    }

    public function update(UpdateCohortRequest $request, Cohort $cohort): RedirectResponse
    {
        $cohort->update($request->validated());

        return redirect()->route('admin.cohorts.index')->with('status', 'Cohort updated successfully.');
    }

    public function destroy(Cohort $cohort): RedirectResponse
    {
        // Startups already assigned to this cohort keep their cohort_id set
        // to null (see the nullOnDelete() FK) rather than being blocked or
        // cascaded — their cohort_number (used everywhere else in the app)
        // is untouched either way.
        $cohort->delete();

        return redirect()->route('admin.cohorts.index')->with('status', 'Cohort removed.');
    }
}

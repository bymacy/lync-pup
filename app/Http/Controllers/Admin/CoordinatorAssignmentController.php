<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignCoordinatorRequest;
use App\Models\Coordinator;
use App\Models\Startup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CoordinatorAssignmentController extends Controller
{
    public function store(AssignCoordinatorRequest $request, Startup $startup): RedirectResponse
    {
        DB::transaction(function () use ($request, $startup) {
            $startup->coordinatorAssignments()->where('assignment_status', 'Active')
                ->update(['assignment_status' => 'Completed']);

            $startup->coordinatorAssignments()->create([
                'coordinator_id' => $request->validated('coordinator_id'),
                'assigned_date' => now(),
                'assignment_status' => 'Active',
            ]);

            Coordinator::whereKey($request->validated('coordinator_id'))
                ->increment('assigned_startups_count');
        });

        return redirect()
            ->route('admin.startups.show', $startup)
            ->with('status', 'Portfolio Coordinator assigned successfully.');
    }
}
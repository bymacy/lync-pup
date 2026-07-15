<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Startup;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StartupProfileController extends Controller
{
    public function index(Request $request): View
    {
        $query = Startup::query()->with(['informationSheet', 'activeCoordinatorAssignment.coordinator']);

        $query = match ($request->query('tab', 'all')) {
            'active' => $query->active(),
            'assign-coordinator' => $query->needsCoordinator(),
            'pending' => $query->pending(),
            default => $query,
        };

        $startups = $query->latest()->paginate(12)->withQueryString();

        return view('admin.startups.index', [
            'startups' => $startups,
            'activeTab' => $request->query('tab', 'all'),
            'totals' => [
                'total' => Startup::count(),
                'active' => Startup::active()->count(),
                'needsCoordinator' => Startup::needsCoordinator()->count(),
                'pending' => Startup::pending()->count(),
            ],
        ]);
    }

    public function show(Startup $startup): View
    {
        $startup->load([
            'user', 'informationSheet', 'teamMembers',
            'latestReadinessAssessment', 'activeCoordinatorAssignment.coordinator',
        ]);

        return view('admin.startups.show', compact('startup'));
    }
}
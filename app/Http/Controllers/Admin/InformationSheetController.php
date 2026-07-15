<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectInformationSheetRequest;
use App\Models\Startup;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InformationSheetController extends Controller
{
    public function show(Startup $startup): View
    {
        $startup->load(['informationSheet', 'teamMembers', 'user']);

        return view('admin.information-sheets.show', compact('startup'));
    }

    public function approve(Startup $startup): RedirectResponse
    {
        $startup->informationSheet()->update([
            'approval_status' => 'Approved',
            'evaluator_remarks' => null,
        ]);

        return redirect()
            ->route('admin.startups.show', $startup)
            ->with('status', 'Information sheet approved.');
    }

    public function reject(RejectInformationSheetRequest $request, Startup $startup): RedirectResponse
    {
        $startup->informationSheet()->update([
            'approval_status' => 'Rejected',
            'evaluator_remarks' => $request->validated('evaluator_remarks'),
        ]);

        return redirect()
            ->route('admin.startups.show', $startup)
            ->with('status', 'Information sheet rejected.');
    }
}
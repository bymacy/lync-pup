<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIncubationInvolvementRequest;
use App\Http\Requests\Admin\StoreLdInterventionRequest;
use App\Http\Requests\Admin\StoreStartupReferenceRequest;
use App\Http\Requests\Admin\StoreTeamMemberRequest;
use App\Http\Requests\Admin\UpdateIncubationInvolvementRequest;
use App\Http\Requests\Admin\UpdateInformationSheetRequest;
use App\Http\Requests\Admin\UpdateLdInterventionRequest;
use App\Http\Requests\Admin\UpdateStartupReferenceRequest;
use App\Http\Requests\Admin\UpdateTeamMemberRequest;
use App\Notifications\InformationSheetApproved;
use App\Models\IncubationInvolvement;
use App\Models\LdIntervention;
use App\Models\StartupReference;
use App\Models\Startup;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InformationSheetController extends Controller
{
    public function show(Startup $startup): View
    {
        $startup->load([
            'informationSheet.incubationInvolvements',
            'informationSheet.ldInterventions',
            'informationSheet.references',
            'teamMembers',
            'user',
        ]);

        $nameParts = \App\Models\InformationSheet::splitFounderName($startup->user?->name);

        return view('admin.information-sheets.show', [
            'startup' => $startup,
            // Seeds empty fields from the Startup Profile — display only, never
            // written until the sheet itself is saved.
            'prefill' => [
                'surname' => $nameParts['surname'],
                'first_name' => $nameParts['first_name'],
                'middle_name' => $nameParts['middle_name'],
                'mobile_no' => (string) $startup->contact_phone,
                'founder_email' => (string) $startup->user?->email,
            ],
        ]);
    }

    public function approve(Startup $startup): RedirectResponse
    {
        abort_if(
            ! $startup->evaluationReached(),
            403,
            'This startup\'s evaluation must be scheduled and its date reached before their Information Sheet can be approved.'
        );

        // Captured before the update so re-approving an already-approved sheet
        // (the admin can revisit this action) doesn't re-notify the founder.
        $wasApproved = $startup->hasApprovedInformationSheet();

        $startup->informationSheet()->update([
            'approval_status' => 'Approved',
            // Stamped so the evaluation roster can tell a sheet approved on the
            // evaluation day (DONE) from one signed off later (MISSED) - see
            // EvaluationSchedule::approvedOnEvaluationDay().
            'approved_at' => now(),
            'evaluator_remarks' => null,
        ]);

        if (! $wasApproved) {
            // This is the moment Meeting / Submission / Readiness Result unlock
            // for the founder, so it gets a dashboard card of its own.
            $startup->user?->notify(new InformationSheetApproved);
        }

        return redirect()
            ->route('admin.assessment-hub.index', ['tab' => 'approved'])
            ->with('status', 'Information sheet approved.')
            ->with('just_approved', true);
    }

    public function update(UpdateInformationSheetRequest $request, Startup $startup): RedirectResponse
    {
        $sheet = $startup->informationSheet()->firstOrCreate(['startup_id' => $startup->startup_id]);

        // Approval only locks the founder out (see Startup\InformationSheetController)
        // — an admin can still revise a sheet after it's approved, e.g. to fix a
        // typo the founder reported after the fact.
        $data = $request->validated();

        // Admin edits are corrections made on the founder's behalf, so they
        // must not re-date the founder's declaration. Only fill it when the
        // sheet has never been accomplished.
        if (! $sheet->date_accomplished) {
            $data['date_accomplished'] = now();
        }

        $sheet->update($data);

        return redirect()->route('admin.information-sheet.show', $startup)->with('status', 'Information Sheet updated.');
    }

    // Team Members
    public function storeTeamMember(StoreTeamMemberRequest $request, Startup $startup): RedirectResponse
    {
        $startup->teamMembers()->create($request->validated());

        return redirect()->route('admin.information-sheet.show', $startup)->with('status', 'Team member added.');
    }

    public function updateTeamMember(UpdateTeamMemberRequest $request, TeamMember $teamMember): RedirectResponse
    {
        $teamMember->update($request->validated());

        return redirect()->route('admin.information-sheet.show', $teamMember->startup)->with('status', 'Team member updated.');
    }

    public function destroyTeamMember(TeamMember $teamMember): RedirectResponse
    {
        $startup = $teamMember->startup;
        $teamMember->delete();

        return redirect()->route('admin.information-sheet.show', $startup)->with('status', 'Team member removed.');
    }

    // Incubation Involvement
    public function storeIncubation(StoreIncubationInvolvementRequest $request, Startup $startup): RedirectResponse
    {
        $sheet = $startup->informationSheet()->firstOrCreate(['startup_id' => $startup->startup_id]);
        $sheet->incubationInvolvements()->create($request->validated());

        return redirect()->route('admin.information-sheet.show', $startup)->with('status', 'Incubation involvement added.');
    }

    public function updateIncubation(UpdateIncubationInvolvementRequest $request, IncubationInvolvement $incubationInvolvement): RedirectResponse
    {
        $incubationInvolvement->update($request->validated());

        return redirect()->route('admin.information-sheet.show', $incubationInvolvement->informationSheet->startup)->with('status', 'Updated.');
    }

    public function destroyIncubation(IncubationInvolvement $incubationInvolvement): RedirectResponse
    {
        $startup = $incubationInvolvement->informationSheet->startup;
        $incubationInvolvement->delete();

        return redirect()->route('admin.information-sheet.show', $startup)->with('status', 'Removed.');
    }

    // L&D Interventions
    public function storeLd(StoreLdInterventionRequest $request, Startup $startup): RedirectResponse
    {
        $sheet = $startup->informationSheet()->firstOrCreate(['startup_id' => $startup->startup_id]);
        $sheet->ldInterventions()->create($request->validated());

        return redirect()->route('admin.information-sheet.show', $startup)->with('status', 'L&D intervention added.');
    }

    public function updateLd(UpdateLdInterventionRequest $request, LdIntervention $ldIntervention): RedirectResponse
    {
        $ldIntervention->update($request->validated());

        return redirect()->route('admin.information-sheet.show', $ldIntervention->informationSheet->startup)->with('status', 'Updated.');
    }

    public function destroyLd(LdIntervention $ldIntervention): RedirectResponse
    {
        $startup = $ldIntervention->informationSheet->startup;
        $ldIntervention->delete();

        return redirect()->route('admin.information-sheet.show', $startup)->with('status', 'Removed.');
    }

    // References
    public function storeReference(StoreStartupReferenceRequest $request, Startup $startup): RedirectResponse
    {
        $sheet = $startup->informationSheet()->firstOrCreate(['startup_id' => $startup->startup_id]);
        $sheet->references()->create($request->validated());

        return redirect()->route('admin.information-sheet.show', $startup)->with('status', 'Reference added.');
    }

    public function updateReference(UpdateStartupReferenceRequest $request, StartupReference $reference): RedirectResponse
    {
        $reference->update($request->validated());

        return redirect()->route('admin.information-sheet.show', $reference->informationSheet->startup)->with('status', 'Updated.');
    }

    public function destroyReference(StartupReference $reference): RedirectResponse
    {
        $startup = $reference->informationSheet->startup;
        $reference->delete();

        return redirect()->route('admin.information-sheet.show', $startup)->with('status', 'Removed.');
    }
}

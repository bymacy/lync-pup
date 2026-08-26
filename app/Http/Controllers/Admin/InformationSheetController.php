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
use App\Models\IncubationInvolvement;
use App\Models\LdIntervention;
use App\Models\StartupReference;
use App\Models\Startup;
use App\Models\TeamMember;
use App\Traits\CompressesImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InformationSheetController extends Controller
{
    use CompressesImages;

    public function show(Startup $startup): View
    {
        $startup->load([
            'informationSheet.incubationInvolvements',
            'informationSheet.ldInterventions',
            'informationSheet.references',
            'teamMembers',
            'user',
        ]);

        return view('admin.information-sheets.show', compact('startup'));
    }

    public function approve(Startup $startup): RedirectResponse
    {
        abort_if(
            ! $startup->hasScheduledEvaluation(),
            403,
            'This startup must have a scheduled evaluation before their Information Sheet can be approved.'
        );

        $startup->informationSheet()->update([
            'approval_status' => 'Approved',
            'evaluator_remarks' => null,
        ]);

        return redirect()
            ->route('admin.assessment-hub.index', ['tab' => 'approved'])
            ->with('status', 'Information sheet approved.')
            ->with('just_approved', true);
    }

    public function update(UpdateInformationSheetRequest $request, Startup $startup): RedirectResponse
    {
        $sheet = $startup->informationSheet()->firstOrCreate(['startup_id' => $startup->startup_id]);

        abort_if($sheet->approval_status === 'Approved', 403, 'This Information Sheet is approved and locked.');

        $data = $request->validated();
        unset($data['founder_signature'], $data['director_signature']);

        if ($request->hasFile('founder_signature')) {
            if ($sheet->founder_signature_path) {
                Storage::disk('public')->delete($sheet->founder_signature_path);
            }
            $data['founder_signature_path'] = $this->compressAndStoreImage($request->file('founder_signature'), 'signatures');
        }

        if ($request->hasFile('director_signature')) {
            if ($sheet->director_signature_path) {
                Storage::disk('public')->delete($sheet->director_signature_path);
            }
            $data['director_signature_path'] = $this->compressAndStoreImage($request->file('director_signature'), 'signatures');
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

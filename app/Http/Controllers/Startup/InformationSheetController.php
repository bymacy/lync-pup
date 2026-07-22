<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Startup\StoreIncubationInvolvementRequest;
use App\Http\Requests\Startup\StoreLdInterventionRequest;
use App\Http\Requests\Startup\StoreStartupReferenceRequest;
use App\Http\Requests\Startup\UpdateIncubationInvolvementRequest;
use App\Http\Requests\Startup\UpdateInformationSheetRequest;
use App\Http\Requests\Startup\UpdateLdInterventionRequest;
use App\Http\Requests\Startup\UpdateStartupReferenceRequest;
use App\Models\IncubationInvolvement;
use App\Models\LdIntervention;
use App\Models\StartupReference;
use App\Traits\CompressesImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InformationSheetController extends Controller
{
    use CompressesImages;

    public function edit(): View
    {
        $startup = auth()->user()->startup->load([
            'informationSheet.incubationInvolvements',
            'informationSheet.ldInterventions',
            'informationSheet.references',
            'teamMembers',
        ]);

        return view('startup.information-sheet.edit', compact('startup'));
    }

    public function update(UpdateInformationSheetRequest $request): RedirectResponse
    {
        $startup = auth()->user()->startup;
        $data = $request->validated();

        $sheet = $startup->informationSheet()->firstOrCreate(['startup_id' => $startup->startup_id]);

        if ($request->hasFile('founder_signature')) {
            if ($sheet->founder_signature_path) {
                Storage::disk('public')->delete($sheet->founder_signature_path);
            }
            $data['founder_signature_path'] = $this->compressAndStoreImage($request->file('founder_signature'), 'signatures');
        }

        $data['approval_status'] = 'Pending';
        $data['submission_date'] = now();

        $sheet->update($data);

        return redirect()->route('startup.information-sheet.edit')->with('status', 'Information Sheet saved and submitted for review.');
    }

    // Incubation Involvement
    public function storeIncubation(StoreIncubationInvolvementRequest $request): RedirectResponse
    {
        $sheet = auth()->user()->startup->informationSheet;
        $sheet->incubationInvolvements()->create($request->validated());

        return redirect()->route('startup.information-sheet.edit')->with('status', 'Incubation involvement added.');
    }

    public function updateIncubation(UpdateIncubationInvolvementRequest $request, IncubationInvolvement $incubationInvolvement): RedirectResponse
    {
        abort_unless($incubationInvolvement->informationSheet->startup_id === auth()->user()->startup->startup_id, 403);
        $incubationInvolvement->update($request->validated());

        return redirect()->route('startup.information-sheet.edit')->with('status', 'Updated.');
    }

    public function destroyIncubation(IncubationInvolvement $incubationInvolvement): RedirectResponse
    {
        abort_unless($incubationInvolvement->informationSheet->startup_id === auth()->user()->startup->startup_id, 403);
        $incubationInvolvement->delete();

        return redirect()->route('startup.information-sheet.edit')->with('status', 'Removed.');
    }

    // L&D Interventions
    public function storeLd(StoreLdInterventionRequest $request): RedirectResponse
    {
        $sheet = auth()->user()->startup->informationSheet;
        $sheet->ldInterventions()->create($request->validated());

        return redirect()->route('startup.information-sheet.edit')->with('status', 'L&D intervention added.');
    }

    public function updateLd(UpdateLdInterventionRequest $request, LdIntervention $ldIntervention): RedirectResponse
    {
        abort_unless($ldIntervention->informationSheet->startup_id === auth()->user()->startup->startup_id, 403);
        $ldIntervention->update($request->validated());

        return redirect()->route('startup.information-sheet.edit')->with('status', 'Updated.');
    }

    public function destroyLd(LdIntervention $ldIntervention): RedirectResponse
    {
        abort_unless($ldIntervention->informationSheet->startup_id === auth()->user()->startup->startup_id, 403);
        $ldIntervention->delete();

        return redirect()->route('startup.information-sheet.edit')->with('status', 'Removed.');
    }

    // References
    public function storeReference(StoreStartupReferenceRequest $request): RedirectResponse
    {
        $sheet = auth()->user()->startup->informationSheet;
        $sheet->references()->create($request->validated());

        return redirect()->route('startup.information-sheet.edit')->with('status', 'Reference added.');
    }

    public function updateReference(UpdateStartupReferenceRequest $request, StartupReference $reference): RedirectResponse
    {
        abort_unless($reference->informationSheet->startup_id === auth()->user()->startup->startup_id, 403);
        $reference->update($request->validated());

        return redirect()->route('startup.information-sheet.edit')->with('status', 'Updated.');
    }

    public function destroyReference(StartupReference $reference): RedirectResponse
    {
        abort_unless($reference->informationSheet->startup_id === auth()->user()->startup->startup_id, 403);
        $reference->delete();

        return redirect()->route('startup.information-sheet.edit')->with('status', 'Removed.');
    }
}
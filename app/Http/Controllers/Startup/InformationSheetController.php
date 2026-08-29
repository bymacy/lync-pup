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
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InformationSheetController extends Controller
{
    public function edit(): View|RedirectResponse
    {
        $startup = auth()->user()->startup->load([
            'informationSheet.incubationInvolvements',
            'informationSheet.ldInterventions',
            'informationSheet.references',
            'teamMembers',
        ]);

        // The Information Sheet is a step that comes after the Startup
        // Profile in the onboarding tracker — sent back to finish the
        // Profile first rather than shown a form they can't meaningfully
        // submit yet (their name/photo/contact details would still be
        // missing from every generated export).
        if (! $startup->isProfileComplete()) {
            return redirect()
                ->route('startup.profile.edit')
                ->with('status', 'Please complete your Startup Profile first before filling out the Information Sheet.');
        }

        return view('startup.information-sheet.edit', [
            'startup' => $startup,
            'prefill' => $this->prefillFromProfile($startup),
        ]);
    }

    public function update(UpdateInformationSheetRequest $request): RedirectResponse
    {
        $startup = auth()->user()->startup;
        $sheet = $startup->informationSheet()->firstOrCreate(['startup_id' => $startup->startup_id]);

        abort_unless($startup->isProfileComplete(), 403, 'Please complete your Startup Profile first before filling out the Information Sheet.');
        abort_if($sheet->approval_status === 'Approved', 403, 'This Information Sheet is approved and locked. Contact your Coordinator for changes.');
        abort_if($startup->evaluationDayLockActive(), 403, 'This Information Sheet is locked because your evaluation day has started. Contact your Coordinator for changes.');

        $data = $request->validated();

        $data['approval_status'] = 'Pending';
        $data['submission_date'] = now();

        // "Date accomplished" is the day the founder filled the form in, so it
        // is stamped here rather than typed — every save re-dates the sheet,
        // matching submission_date above.
        $data['date_accomplished'] = now();

        $sheet->update($data);

        return redirect()->route('startup.information-sheet.edit')->with('status', 'Information Sheet saved and submitted for review.');
    }

    /**
     * Values used to pre-fill sheet fields that are still empty, taken from
     * the Startup Profile the founder already completed. Nothing is written to
     * the database here — these only seed the inputs, so the founder reviews
     * and corrects them before the first save.
     *
     * One-way on purpose: the sheet's copy is its own record (the founder can
     * edit it freely here) and saving it never writes back to the Startup
     * Profile or the user account. Fields whose shapes don't match (profile
     * "location" is a city, the sheet wants a street address) are left out.
     */
    private function prefillFromProfile($startup): array
    {
        $name = \App\Models\InformationSheet::splitFounderName($startup->user?->name);

        return [
            'surname' => $name['surname'],
            'first_name' => $name['first_name'],
            'middle_name' => $name['middle_name'],
            'mobile_no' => (string) $startup->contact_phone,
            'founder_email' => (string) $startup->user?->email,
        ];
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
<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Startup\StoreTeamMemberRequest;
use App\Http\Requests\Startup\UpdateStartupProfileRequest;
use App\Http\Requests\Startup\UpdateTeamMemberDetailsRequest;
use App\Http\Requests\Startup\UpdateTeamMemberRequest;

use App\Models\Startup;
use App\Models\StartupTeamMember;
use App\Models\TeamMember;
use App\Traits\CompressesImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StartupProfileController extends Controller
{
    use CompressesImages;

    public function edit(): View
    {
        $startup = auth()->user()->startup->load([
            'informationSheet',
            'startupTeamMembers',
            'activeCoordinatorAssignment.coordinator',
            'latestReadinessAssessment',
        ]);

        return view('startup.profile.edit', compact('startup'));
    }

    public function update(UpdateStartupProfileRequest $request): RedirectResponse
    {
        $startup = auth()->user()->startup;
        $data = $request->validated();

        // Business Description lives on the Startup itself (see migration
        // 000048), so it's a normal Profile field - no lock to check, same
        // as company_name or industry_sector.
        $startup->update([
            'company_name' => $data['company_name'],
            'industry_sector' => $data['industry_sector'],
            'business_description' => $data['business_description'],
            'contact_phone' => $data['contact_phone'] ?? null,
            'website' => $data['website'] ?? null,
            'location' => $data['location'] ?? null,


        ]);

        // The Information Sheet's own business_description column is only
        // ever pre-filled from the Profile's while it's still blank - after
        // that first fill the two are fully independent, same split
        // startup_overview already got from this same column (migration
        // 000043). Not gated on the lock at all: it either still needs its
        // one-time seed, or it doesn't.
        if (blank($startup->informationSheet?->business_description)) {
            $startup->informationSheet()->updateOrCreate(
                ['startup_id' => $startup->startup_id],
                ['business_description' => $data['business_description']]
            );
        }

        // The Profile's own Core Team roster (StartupTeamMember, migration
        // 000049) - separate from the Information Sheet's own teamMembers
        // (TeamMember), so this never checks the sheet's lock. Editing,
        // adding or removing someone here never touches Section II.
        if ($request->has('team_members')) {
            foreach ($request->team_members as $id => $name) {

                if (blank($name)) {
                    continue;
                }

                StartupTeamMember::where('startup_team_member_id', $id)
                    ->where('startup_id', $startup->startup_id)
                    ->update([
                        'full_name' => $name,
                    ]);
            }
        }

        if ($request->has('new_team_members')) {

            foreach ($request->new_team_members as $name) {
                if (blank($name)) {
                    continue;
                }

                $startup->startupTeamMembers()->create([
                    'full_name' => $name,
                ]);
            }
        }

        if ($request->has('deleted_team_members')) {

            StartupTeamMember::where('startup_id', $startup->startup_id)
                ->whereIn('startup_team_member_id', $request->deleted_team_members)
                ->delete();
        }

        // The Information Sheet's own Core Team table (Section II) is only
        // ever seeded from the Profile's roster once, while it's still
        // empty - after that the founder manages it entirely through the
        // Information Sheet's own Core Team CRUD (storeTeamMember() etc.
        // below), which is what stays locked. Only full_name transfers;
        // the biographical columns are left for the founder to fill in
        // there.
        if ($startup->teamMembers()->count() === 0) {
            $startup->startupTeamMembers->each(function (StartupTeamMember $member) use ($startup) {
                $startup->teamMembers()->create([
                    'full_name' => $member->full_name,
                ]);
            });
        }

        auth()->user()->update(['name' => $data['founder_name']]);

        if ($request->hasFile('startup_photo')) {
            if ($startup->startup_photo_path) {
                Storage::disk('public')->delete($startup->startup_photo_path);
            }
            $startup->update([
                'startup_photo_path' => $this->compressAndStoreImage($request->file('startup_photo'), 'startups'),
            ]);
        }

        return redirect()->route('startup.profile.edit')->with('status', 'Startup Profile updated successfully.');
    }

    public function storeTeamMember(StoreTeamMemberRequest $request): RedirectResponse|Response
    {
        $startup = auth()->user()->startup;
        $this->abortIfInformationSheetLocked($startup);

        // This route is also used by the Information Sheet's Core Team "add
        // row" form (see the _dry_run note on
        // InformationSheetController::update()) — its all-or-nothing save
        // dry-runs every section, this one included, before persisting any
        // of them.
        if ($request->boolean('_dry_run')) {
            return response()->noContent();
        }

        $startup->teamMembers()->create($request->validated());

        return redirect()->route('startup.profile.edit')->with('status', 'Team member added.');
    }

    public function updateTeamMember(UpdateTeamMemberRequest $request, TeamMember $teamMember): RedirectResponse
    {
        $startup = auth()->user()->startup;
        abort_unless($teamMember->startup_id === $startup->startup_id, 403);
        $this->abortIfInformationSheetLocked($startup);

        $teamMember->update($request->validated());

        return redirect()->route('startup.profile.edit')->with('status', 'Team member updated.');
    }

    public function destroyTeamMember(TeamMember $teamMember): RedirectResponse
    {
        $startup = auth()->user()->startup;
        abort_unless($teamMember->startup_id === $startup->startup_id, 403);
        $this->abortIfInformationSheetLocked($startup);

        // The Information Sheet's Core Team table is the only caller of this
        // route, and Section II must never drop below the 3-member minimum
        // (see UpdateStartupProfileRequest for the same floor on the
        // Startup Profile page's own bulk save).
        abort_if(
            $teamMember->startup->teamMembers()->count() <= 3,
            422,
            'The Core Team table must keep at least 3 entries. Add a replacement before removing this one.'
        );

        $teamMember->delete();

        return redirect()->route('startup.profile.edit')->with('status', 'Team member removed.');
    }

    public function updateTeamMemberDetails(UpdateTeamMemberDetailsRequest $request, TeamMember $teamMember): RedirectResponse|Response
    {
        $startup = auth()->user()->startup;
        abort_unless($teamMember->startup_id === $startup->startup_id, 403);
        $this->abortIfInformationSheetLocked($startup);

        // See the _dry_run note on InformationSheetController::update() —
        // the Information Sheet page validates every section (this Core Team
        // row included) before persisting any of them, so a typo in one row
        // can't leave another row's — or the main sheet's — save half-done.
        if ($request->boolean('_dry_run')) {
            return response()->noContent();
        }

        $teamMember->update($request->validated());

        return redirect()->route('startup.information-sheet.edit')->with('status', 'Team member updated.');
    }

    /**
     * Same two conditions, and the same wording, as
     * InformationSheetController::update()'s own lock check - guards the
     * Information Sheet's own Core Team CRUD above (TeamMember). The
     * Profile's own roster (StartupTeamMember, in update() above) is
     * independent and never checks this.
     */
    private function abortIfInformationSheetLocked(Startup $startup): void
    {
        abort_if($startup->hasApprovedInformationSheet(), 403, 'This Information Sheet is approved and locked. Contact your Coordinator for changes.');
        abort_if($startup->evaluationDayLockActive(), 403, 'This Information Sheet is locked for today - your evaluation is scheduled today. It reopens tomorrow if the evaluation does not push through.');
    }
}

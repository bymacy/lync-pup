<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Startup\StoreTeamMemberRequest;
use App\Http\Requests\Startup\UpdateStartupProfileRequest;
use App\Http\Requests\Startup\UpdateTeamMemberDetailsRequest;
use App\Http\Requests\Startup\UpdateTeamMemberRequest;

use App\Models\Startup;
use App\Models\TeamMember;
use App\Traits\CompressesImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StartupProfileController extends Controller
{
    use CompressesImages;

    public function edit(): View
    {
        $startup = auth()->user()->startup->load([
            'informationSheet',
            'teamMembers',
            'activeCoordinatorAssignment.coordinator',
            'latestReadinessAssessment',
        ]);

        return view('startup.profile.edit', compact('startup'));
    }

    public function update(UpdateStartupProfileRequest $request): RedirectResponse
    {
        $startup = auth()->user()->startup;
        $data = $request->validated();

        $startup->update([
            'company_name' => $data['company_name'],
            'industry_sector' => $data['industry_sector'],
            'contact_phone' => $data['contact_phone'] ?? null,
            'website' => $data['website'] ?? null,
            'location' => $data['location'] ?? null,


        ]);

        if ($request->has('team_members')) {
            foreach ($request->team_members as $id => $name) {

                if (blank($name)) {
                    continue;
                }

                TeamMember::where('member_id', $id)
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

                $startup->teamMembers()->create([
                    'full_name' => $name,
                ]);
            }
        }

        if ($request->has('deleted_team_members')) {

            TeamMember::where('startup_id', $startup->startup_id)
                ->whereIn('member_id', $request->deleted_team_members)
                ->delete();
        }

        $startup->informationSheet()->updateOrCreate(
            ['startup_id' => $startup->startup_id],
            ['business_description' => $data['business_description']]
        );

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

    public function storeTeamMember(StoreTeamMemberRequest $request): RedirectResponse
    {
        auth()->user()->startup->teamMembers()->create($request->validated());

        return redirect()->route('startup.profile.edit')->with('status', 'Team member added.');
    }

    public function updateTeamMember(UpdateTeamMemberRequest $request, TeamMember $teamMember): RedirectResponse
    {
        abort_unless($teamMember->startup_id === auth()->user()->startup->startup_id, 403);

        $teamMember->update($request->validated());

        return redirect()->route('startup.profile.edit')->with('status', 'Team member updated.');
    }

    public function destroyTeamMember(TeamMember $teamMember): RedirectResponse
    {
        abort_unless($teamMember->startup_id === auth()->user()->startup->startup_id, 403);

        // The Information Sheet's Core Team table is the only caller of this
        // route, and Section II must never be left empty.
        abort_if(
            $teamMember->startup->teamMembers()->count() <= 1,
            422,
            'The Core Team table must keep at least one entry. Replace this one instead of removing it.'
        );

        $teamMember->delete();

        return redirect()->route('startup.profile.edit')->with('status', 'Team member removed.');
    }

    public function updateTeamMemberDetails(UpdateTeamMemberDetailsRequest $request, TeamMember $teamMember): RedirectResponse
    {
        abort_unless($teamMember->startup_id === auth()->user()->startup->startup_id, 403);

        $teamMember->update($request->validated());

        return redirect()->route('startup.information-sheet.edit')->with('status', 'Team member updated.');
    }
}

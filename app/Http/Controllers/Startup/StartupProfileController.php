<?php

namespace App\Http\Controllers\Startup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Startup\StoreTeamMemberRequest;
use App\Http\Requests\Startup\UpdateStartupProfileRequest;
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
            'informationSheet', 'teamMembers', 'activeCoordinatorAssignment.coordinator', 'latestReadinessAssessment',
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

        $teamMember->delete();

        return redirect()->route('startup.profile.edit')->with('status', 'Team member removed.');
    }
}
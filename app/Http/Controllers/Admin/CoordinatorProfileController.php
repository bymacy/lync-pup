<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCoordinatorRequest;
use App\Http\Requests\Admin\UpdateCoordinatorRequest;
use App\Models\Coordinator;
use App\Models\Roadblock;
use App\Traits\CompressesImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CoordinatorProfileController extends Controller
{
    use CompressesImages;

    public function index(): View
    {
        // Eager-loaded (with their startup) so the "X Startup" stat on each
        // coordinator card can list the actual startups behind that count
        // without an extra query per click — see
        // Coordinator::getActiveStartupsCountAttribute(), which reads from
        // this loaded collection instead of the stale assigned_startups_count
        // column (only ever incremented, never decremented — see
        // CoordinatorAssignmentController::store()).
        $coordinators = Coordinator::with(['assignments' => function ($query) {
            $query->where('assignment_status', 'Active')->with('startup')->latest();
        }])->latest()->get();

        return view('admin.coordinators.index', compact('coordinators'));
    }

    public function store(StoreCoordinatorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['name'] = trim("{$data['honorific']} {$data['first_name']} {$data['last_name']}");
        $data['role_title'] = 'Portfolio Coordinator';

        if (! empty($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        if ($request->hasFile('coordinator_photo')) {
            try {
                $data['coordinator_photo_path'] = $this->compressAndStoreImage($request->file('coordinator_photo'), 'coordinators');
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages([
                    'coordinator_photo' => "That photo couldn't be processed ({$e->getMessage()}). Please try a different file.",
                ]);
            }
        }

        Coordinator::create($data);

        return redirect()->route('admin.coordinators.index')->with('status', 'Coordinator added successfully.');
    }

    public function update(UpdateCoordinatorRequest $request, Coordinator $coordinator): RedirectResponse
    {
        $data = $request->validated();
        $data['name'] = trim("{$data['honorific']} {$data['first_name']} {$data['last_name']}");
        $data['role_title'] = 'Portfolio Coordinator';

        if (! empty($data['email'])) {
            $data['email'] = strtolower($data['email']);
        }

        if ($request->hasFile('coordinator_photo')) {
            // Compress the new photo *before* touching the old one — if
            // processing fails, the coordinator keeps their existing photo
            // instead of ending up with none at all.
            try {
                $newPhotoPath = $this->compressAndStoreImage($request->file('coordinator_photo'), 'coordinators');
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages([
                    'coordinator_photo' => "That photo couldn't be processed ({$e->getMessage()}). Please try a different file.",
                ]);
            }

            if ($coordinator->coordinator_photo_path) {
                Storage::disk('public')->delete($coordinator->coordinator_photo_path);
            }
            $data['coordinator_photo_path'] = $newPhotoPath;
        }

        $coordinator->update($data);

        return redirect()->route('admin.coordinators.index')->with('status', 'Coordinator updated successfully.');
    }

    public function destroy(Coordinator $coordinator): RedirectResponse
    {
        // Same fix as MentorController::destroy() — see its comment. The
        // coordinator_id FK is ON DELETE SET NULL, so without this, any
        // roadblock still assigned to this coordinator would be left stuck
        // as "Scheduled"/"Pending Review" with a blank assignee column
        // instead of reappearing in the Pending list.
        $coordinator->roadblocks()
            ->whereIn('status', Roadblock::ACTIVE_STATUSES)
            ->get()
            ->each(fn (Roadblock $roadblock) => $roadblock->update(Roadblock::pendingResetAttributes()));

        // Same reasoning as MentorController::destroy() — snapshot the name
        // onto closed-out roadblocks before the FK nulls coordinator_id out
        // from under them, so Archive can still say who it was.
        $coordinator->roadblocks()
            ->whereIn('status', ['Resolved', 'Failed', 'Deleted by Admin'])
            ->update(['assignee_name_snapshot' => $coordinator->display_name]);

        if ($coordinator->coordinator_photo_path) {
            Storage::disk('public')->delete($coordinator->coordinator_photo_path);
        }

        $coordinator->delete();

        return redirect()->route('admin.coordinators.index')->with('status', 'Coordinator removed.');
    }
}
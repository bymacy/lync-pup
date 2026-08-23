<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMentorRequest;
use App\Http\Requests\Admin\UpdateMentorRequest;
use App\Models\Mentor;
use App\Models\Roadblock;
use App\Traits\CompressesImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MentorController extends Controller
{
    use CompressesImages;

    public function index(): View
    {
        $mentors = Mentor::latest()->get();

        return view('admin.mentors.index', compact('mentors'));
    }

    public function store(StoreMentorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['full_name'] = trim("{$data['honorific']} {$data['first_name']} {$data['last_name']}");

        if (! empty($data['contact_email'])) {
            $data['contact_email'] = strtolower($data['contact_email']);
        }

        if ($request->hasFile('mentor_photo')) {
            $data['mentor_photo_path'] = $this->compressAndStoreImage($request->file('mentor_photo'), 'mentors');
        }

        Mentor::create($data);

        return redirect()->route('admin.mentors.index')->with('status', 'Mentor added successfully.');
    }

    public function update(UpdateMentorRequest $request, Mentor $mentor): RedirectResponse
    {
        $data = $request->validated();
        $data['full_name'] = trim("{$data['honorific']} {$data['first_name']} {$data['last_name']}");

        if (! empty($data['contact_email'])) {
            $data['contact_email'] = strtolower($data['contact_email']);
        }

        if ($request->hasFile('mentor_photo')) {
            if ($mentor->mentor_photo_path) {
                Storage::disk('public')->delete($mentor->mentor_photo_path);
            }
            $data['mentor_photo_path'] = $this->compressAndStoreImage($request->file('mentor_photo'), 'mentors');
        }

        $mentor->update($data);

        return redirect()->route('admin.mentors.index')->with('status', 'Mentor updated successfully.');
    }

    public function destroy(Mentor $mentor): RedirectResponse
    {
        // The mentor_id FK is ON DELETE SET NULL, so deleting this mentor
        // would otherwise leave any roadblock still assigned to them stuck
        // as "Scheduled"/"Pending Review" with a blank assignee column
        // instead of reappearing in the Pending list — send those back to
        // Pending explicitly first. Already-Resolved/Failed roadblocks are
        // left untouched; they're closed out and losing the mentor_id
        // column there doesn't need to reopen them.
        $mentor->roadblocks()
            ->whereIn('status', Roadblock::ACTIVE_STATUSES)
            ->get()
            ->each(fn (Roadblock $roadblock) => $roadblock->update(Roadblock::pendingResetAttributes()));

        // Those closed-out roadblocks keep their status, but the FK is
        // about to null mentor_id out from under them regardless — capture
        // the name now so Archive can still say who it was, tagged as
        // deleted, instead of showing a blank Mentor column.
        $mentor->roadblocks()
            ->whereIn('status', ['Resolved', 'Failed'])
            ->update(['assignee_name_snapshot' => $mentor->display_name]);

        if ($mentor->mentor_photo_path) {
            Storage::disk('public')->delete($mentor->mentor_photo_path);
        }

        $mentor->delete();

        return redirect()->route('admin.mentors.index')->with('status', 'Mentor removed.');
    }
}
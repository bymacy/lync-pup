<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMentorRequest;
use App\Http\Requests\Admin\UpdateMentorRequest;
use App\Models\Mentor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MentorController extends Controller
{
    public function index(): View
    {
        $mentors = Mentor::latest()->get();

        return view('admin.mentors.index', compact('mentors'));
    }

    public function store(StoreMentorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['full_name'] = trim("{$data['honorific']} {$data['first_name']} {$data['last_name']}");

        if ($request->hasFile('mentor_photo')) {
            $data['mentor_photo_path'] = $request->file('mentor_photo')->store('mentors', 'public');
        }

        Mentor::create($data);

        return redirect()->route('admin.mentors.index')->with('status', 'Mentor added successfully.');
    }

    public function update(UpdateMentorRequest $request, Mentor $mentor): RedirectResponse
    {
        $data = $request->validated();
        $data['full_name'] = trim("{$data['honorific']} {$data['first_name']} {$data['last_name']}");

        if ($request->hasFile('mentor_photo')) {
            if ($mentor->mentor_photo_path) {
                Storage::disk('public')->delete($mentor->mentor_photo_path);
            }
            $data['mentor_photo_path'] = $request->file('mentor_photo')->store('mentors', 'public');
        }

        $mentor->update($data);

        return redirect()->route('admin.mentors.index')->with('status', 'Mentor updated successfully.');
    }

    public function destroy(Mentor $mentor): RedirectResponse
    {
        if ($mentor->mentor_photo_path) {
            Storage::disk('public')->delete($mentor->mentor_photo_path);
        }

        $mentor->delete();

        return redirect()->route('admin.mentors.index')->with('status', 'Mentor removed.');
    }
}
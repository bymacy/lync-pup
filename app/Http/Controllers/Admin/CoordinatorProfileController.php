<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCoordinatorRequest;
use App\Http\Requests\Admin\UpdateCoordinatorRequest;
use App\Models\Coordinator;
use App\Traits\CompressesImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CoordinatorProfileController extends Controller
{
    use CompressesImages;

    public function index(): View
    {
        $coordinators = Coordinator::latest()->get();

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
            $data['coordinator_photo_path'] = $this->compressAndStoreImage($request->file('coordinator_photo'), 'coordinators');
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
            if ($coordinator->coordinator_photo_path) {
                Storage::disk('public')->delete($coordinator->coordinator_photo_path);
            }
            $data['coordinator_photo_path'] = $this->compressAndStoreImage($request->file('coordinator_photo'), 'coordinators');
        }

        $coordinator->update($data);

        return redirect()->route('admin.coordinators.index')->with('status', 'Coordinator updated successfully.');
    }

    public function destroy(Coordinator $coordinator): RedirectResponse
    {
        if ($coordinator->coordinator_photo_path) {
            Storage::disk('public')->delete($coordinator->coordinator_photo_path);
        }

        $coordinator->delete();

        return redirect()->route('admin.coordinators.index')->with('status', 'Coordinator removed.');
    }
}
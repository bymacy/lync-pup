<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMentorRequest;
use App\Http\Requests\Admin\UpdateMentorRequest;
use App\Models\Mentor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
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

        if (! empty($data['contact_email'])) {
            $data['contact_email'] = strtolower($data['contact_email']);
        }

        if ($request->hasFile('mentor_photo')) {
            $data['mentor_photo_path'] = $this->compressAndStoreImage($request->file('mentor_photo'));
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
            $data['mentor_photo_path'] = $this->compressAndStoreImage($request->file('mentor_photo'));
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

    /**
     * Resizes and compresses an uploaded image down to roughly 2MB or less,
     * regardless of original size, so we can accept large phone-camera photos
     * without needing a strict upload size limit.
     */
    private function compressAndStoreImage(UploadedFile $file): string
    {
        $maxBytes = 2 * 1024 * 1024; // ~2MB target
        $maxDimension = 1200;        // no need for headshots larger than this

        $mime = $file->getMimeType();
        $sourcePath = $file->getRealPath();

        $image = match ($mime) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($sourcePath),
            'image/png' => imagecreatefrompng($sourcePath),
            'image/gif' => imagecreatefromgif($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default => throw new \RuntimeException('Unsupported image type.'),
        };

        $width = imagesx($image);
        $height = imagesy($image);

        // Downscale if larger than our max dimension
        if ($width > $maxDimension || $height > $maxDimension) {
            $ratio = min($maxDimension / $width, $maxDimension / $height);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefill($resized, 0, 0, $white);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        // Encode as JPEG, stepping quality down until under our size target
        $quality = 85;
        do {
            ob_start();
            imagejpeg($image, null, $quality);
            $data = ob_get_clean();
            $quality -= 10;
        } while (strlen($data) > $maxBytes && $quality > 10);

        imagedestroy($image);

        $filename = 'mentors/'.uniqid('mentor_').'.jpg';
        Storage::disk('public')->put($filename, $data);

        return $filename;
    }
}
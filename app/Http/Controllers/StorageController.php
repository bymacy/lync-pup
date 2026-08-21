<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class StorageController extends Controller
{
    /**
     * Stream a file straight out of the "public" disk (storage/app/public)
     * as a fallback for machines where `php artisan storage:link` hasn't
     * been run. This is the fix for testers reporting images not loading
     * because "the location of the images cannot be found" on their
     * device — most commonly Windows, where creating a symlink requires
     * Developer Mode or an elevated shell, a privilege testers often don't
     * have or know how to grant themselves.
     *
     * Every image URL in the app is already generated via Storage::url()
     * or a disk's ->url() accessor, both of which resolve to
     * "/storage/{path}" regardless of whether the symlink actually
     * exists. When it does exist, the webserver (or PHP's built-in dev
     * server) serves the physical file directly and this route is never
     * even reached. When it doesn't, this route transparently serves the
     * same bytes instead — so running storage:link becomes unnecessary
     * rather than a required setup step testers can forget or fail to do.
     */
    public function show(string $path): Response
    {
        // Flysystem already rejects ".." path traversal internally, but
        // bail out explicitly first so a malformed path never even
        // reaches the disk.
        if (str_contains($path, '..')) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path);
    }
}

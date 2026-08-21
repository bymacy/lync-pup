<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression tests for testers (commonly on Windows) whose machines never
 * had `php artisan storage:link` run — public disk files must still be
 * reachable at their normal "/storage/{path}" URL via the fallback route
 * in routes/web.php + StorageController, not just via the symlink.
 */
class StorageFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_public_disk_file_is_servable_without_the_storage_symlink(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('mentors/sample.jpg', 'fake-image-bytes');

        $response = $this->get('/storage/mentors/sample.jpg');

        $response->assertOk();
        $response->assertSee('fake-image-bytes', false);
    }

    public function test_a_missing_file_returns_a_404(): void
    {
        Storage::fake('public');

        $this->get('/storage/mentors/does-not-exist.jpg')->assertNotFound();
    }

    public function test_path_traversal_attempts_are_rejected(): void
    {
        Storage::fake('public');

        $this->get('/storage/../.env')->assertNotFound();
    }
}
